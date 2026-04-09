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
        
        return view('settings.index', compact('emailSettings', 'companySettings'));
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
