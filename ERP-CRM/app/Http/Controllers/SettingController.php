<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    public function index()
    {
        $emailSettings = Setting::where('group', 'email')->get();
        
        return view('settings.index', compact('emailSettings'));
    }

    public function updateEmail(Request $request)
    {
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
}
