<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Setting::class);
        
        $emailSettings = Setting::where('group', 'email')->get();
        $companySettings = Setting::where('group', 'company')->get();
        
        $slaConfig = \App\Models\TechnicalTicket::slaConfig();

        return view('settings.index', compact('emailSettings', 'companySettings', 'slaConfig'));
    }

    /**
     * Cấu hình SLA Ticket kỹ thuật: khung giờ làm việc, ngày làm việc, số giờ SLA theo độ ưu tiên, ngày nghỉ lễ.
     */
    public function updateTechnicalSla(Request $request)
    {
        $this->authorize('update', Setting::class);

        $validated = $request->validate([
            'periods' => ['required', 'array', 'min:1', 'max:4'],
            'periods.*.start' => ['nullable', 'date_format:H:i'],
            'periods.*.end' => ['nullable', 'date_format:H:i'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:1,7'],
            'priority_hours.high' => ['required', 'numeric', 'min:0.5', 'max:240'],
            'priority_hours.medium' => ['required', 'numeric', 'min:0.5', 'max:240'],
            'holidays' => ['nullable', 'string', 'max:5000'],
            'recalculate_pending' => ['nullable', 'boolean'],
        ], [
            'working_days.required' => 'Vui lòng chọn ít nhất 1 ngày làm việc.',
            'periods.*.start.date_format' => 'Giờ bắt đầu không hợp lệ (HH:MM).',
            'periods.*.end.date_format' => 'Giờ kết thúc không hợp lệ (HH:MM).',
        ]);

        $periods = [];
        foreach ($validated['periods'] as $i => $period) {
            $start = $period['start'] ?? null;
            $end = $period['end'] ?? null;
            if (!$start && !$end) {
                continue;
            }
            if (!$start || !$end || $start >= $end) {
                return back()->withInput()->withErrors(['periods' => 'Ca ' . ($i + 1) . ': giờ bắt đầu phải nhỏ hơn giờ kết thúc.']);
            }
            $periods[] = [$start, $end];
        }
        usort($periods, fn ($a, $b) => strcmp($a[0], $b[0]));
        if (empty($periods)) {
            return back()->withInput()->withErrors(['periods' => 'Vui lòng nhập ít nhất 1 khung giờ làm việc.']);
        }
        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i][0] < $periods[$i - 1][1]) {
                return back()->withInput()->withErrors(['periods' => 'Các khung giờ làm việc không được chồng lấn nhau.']);
            }
        }

        // Ngày nghỉ lễ: mỗi dòng / dấu phẩy 1 ngày, định dạng dd/mm/yyyy hoặc yyyy-mm-dd
        $holidays = [];
        foreach (preg_split('/[\s,;]+/', (string) ($validated['holidays'] ?? '')) as $raw) {
            if (($raw = trim($raw)) === '') {
                continue;
            }
            try {
                $date = str_contains($raw, '/')
                    ? \Carbon\Carbon::createFromFormat('!d/m/Y', $raw)
                    : \Carbon\Carbon::createFromFormat('!Y-m-d', $raw);
            } catch (\Throwable $e) {
                $date = false;
            }
            if (!$date) {
                return back()->withInput()->withErrors(['holidays' => "Ngày nghỉ \"{$raw}\" không hợp lệ (dd/mm/yyyy)."]);
            }
            $holidays[] = $date->toDateString();
        }
        $holidays = array_values(array_unique($holidays));
        sort($holidays);

        Setting::updateOrCreate(
            ['key' => \App\Models\TechnicalTicket::SLA_SETTING_KEY],
            ['value' => json_encode([
                'periods' => $periods,
                'working_days' => array_values(array_unique(array_map('intval', $validated['working_days']))),
                'priority_hours' => [
                    'high' => (float) $validated['priority_hours']['high'],
                    'medium' => (float) $validated['priority_hours']['medium'],
                ],
                'holidays' => $holidays,
            ]), 'group' => 'technical']
        );
        Cache::forget('setting.' . \App\Models\TechnicalTicket::SLA_SETTING_KEY);
        \App\Models\TechnicalTicket::flushSlaConfig();

        $message = 'Đã lưu cấu hình SLA Ticket kỹ thuật (áp dụng cho ticket tạo mới).';
        if ($request->boolean('recalculate_pending')) {
            $count = 0;
            \App\Models\TechnicalTicket::whereNull('picked_up_at')
                ->whereNotIn('status', ['completed', 'closed'])
                ->whereIn('priority', ['high', 'medium'])
                ->get()
                ->each(function ($ticket) use (&$count) {
                    $ticket->sla_deadline = \App\Models\TechnicalTicket::calculateSlaDeadline($ticket->priority, $ticket->created_at);
                    $ticket->saveQuietly();
                    $count++;
                });
            $message .= " Đã tính lại hạn SLA cho {$count} ticket đang chờ pickup.";
        }

        return redirect()->to(route('settings.index') . '#technical-sla')->with('success', $message);
    }

    public function updateEmail(Request $request)
    {
        $this->authorize('update', Setting::class);
        
        $validated = $request->validate([
            'mail_host' => ['required', 'string'],
            'mail_port' => ['required', 'integer'],
            'mail_username' => ['required', 'string'],
            'mail_password' => ['nullable', 'string'],
            'mail_encryption' => ['required', 'in:tls,ssl'],
            'mail_from_address' => ['required', 'email'],
            'mail_from_name' => ['required', 'string'],
        ]);

        foreach ($validated as $key => $value) {
            // Don't update password if empty (keep old password)
            if ($key === 'mail_password' && empty($value)) {
                continue;
            }
            
            Setting::set($key, $value);
        }

        // Clear config cache
        Artisan::call('config:clear');
        
        return back()->with('success', 'Cài đặt email đã được cập nhật thành công.');
    }

    public function testEmail(Request $request)
    {
        $this->authorize('update', Setting::class);
        
        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        try {
            // Apply current settings
            Setting::applyEmailConfig();
            
            // Send test email
            Mail::raw('Đây là email test từ hệ thống Mini ERP. Nếu bạn nhận được email này, cấu hình email đã hoạt động!', function ($message) use ($validated) {
                $message->to($validated['test_email'])
                        ->subject('Test Email - Mini ERP');
            });

            return back()->with('success', 'Email test đã được gửi đến ' . $validated['test_email']);
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể gửi email: ' . $e->getMessage());
        }
    }

    public function updateCompany(Request $request)
    {
        $this->authorize('update', Setting::class);

        $validated = $request->validate([
            'company_name'         => ['required', 'string', 'max:255'],
            'company_address'      => ['required', 'string', 'max:500'],
            'company_tax_code'     => ['nullable', 'string', 'max:50'],
            'company_phone'        => ['nullable', 'string', 'max:30'],
            'company_fax'          => ['nullable', 'string', 'max:30'],
            'company_website'      => ['nullable', 'string', 'max:255'],
            'company_email'        => ['nullable', 'email', 'max:255'],
            'company_bank_account' => ['nullable', 'string', 'max:500'],
            'company_logo'         => ['nullable', 'image', 'max:2048'],
        ]);

        // Handle logo upload
        $logoSaved = false;
        if ($request->hasFile('company_logo') && $request->file('company_logo')->isValid()) {
            $logoFile = $request->file('company_logo');
            $logoName = 'company_logo_' . time() . '.' . $logoFile->getClientOriginalExtension();

            // Ensure directory exists
            if (!is_dir(public_path('uploads/company'))) {
                mkdir(public_path('uploads/company'), 0755, true);
            }

            $logoFile->move(public_path('uploads/company'), $logoName);
            $logoValue = 'uploads/company/' . $logoName;

            Setting::updateOrCreate(
                ['key' => 'company_logo'],
                ['value' => $logoValue, 'group' => 'company']
            );
            Cache::forget('setting.company_logo');
            $logoSaved = true;
        }
        unset($validated['company_logo']); // already handled above

        // Map company_tax_code -> company_tax for backward compat
        if (isset($validated['company_tax_code'])) {
            Setting::updateOrCreate(
                ['key' => 'company_tax'],
                ['value' => $validated['company_tax_code'], 'group' => 'company']
            );
            Cache::forget('setting.company_tax');
        }

        $fieldMap = [
            'company_name'         => 'company_name',
            'company_address'      => 'company_address',
            'company_tax_code'     => 'company_tax_code',
            'company_phone'        => 'company_phone',
            'company_fax'          => 'company_fax',
            'company_website'      => 'company_website',
            'company_email'        => 'company_email',
            'company_bank_account' => 'company_bank_account',
        ];

        foreach ($fieldMap as $inputKey => $settingKey) {
            if (array_key_exists($inputKey, $validated)) {
                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    ['value' => $validated[$inputKey] ?? '', 'group' => 'company']
                );
                Cache::forget("setting.{$settingKey}");
            }
        }

        return back()->with('success', 'Thông tin công ty đã được cập nhật thành công.');
    }
}
