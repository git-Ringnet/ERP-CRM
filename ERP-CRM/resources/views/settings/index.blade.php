@extends('layouts.app')

@section('title', 'Cài đặt hệ thống')
@section('page-title', 'Cài đặt hệ thống')

@section('content')
<div class="space-y-6">
    <!-- Email Settings -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-envelope mr-2 text-primary"></i>
                Cài đặt Email
            </h3>
            <p class="text-sm text-gray-600 mt-1">Cấu hình SMTP để gửi email hóa đơn cho khách hàng</p>
        </div>

        <form action="{{ route('settings.email.update') }}" method="POST" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- SMTP Host -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        SMTP Host <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="mail_host" 
                           value="{{ old('mail_host', $emailSettings->where('key', 'mail_host')->first()->value ?? 'smtp.gmail.com') }}" 
                           required
                           placeholder="smtp.gmail.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-500 mt-1">
                        Gmail: smtp.gmail.com | Outlook: smtp.office365.com
                    </p>
                </div>

                <!-- SMTP Port -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        SMTP Port <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="mail_port" 
                           value="{{ old('mail_port', $emailSettings->where('key', 'mail_port')->first()->value ?? '587') }}" 
                           required
                           placeholder="587"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-500 mt-1">
                        TLS: 587 | SSL: 465
                    </p>
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Username (Email) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="mail_username" 
                           value="{{ old('mail_username', $emailSettings->where('key', 'mail_username')->first()->value ?? '') }}" 
                           required
                           placeholder="your-email@gmail.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Password / App Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="mail_password" 
                           placeholder="••••••••••••••••"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-500 mt-1">
                        Để trống nếu không muốn thay đổi
                    </p>
                </div>

                <!-- Encryption -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mã hóa <span class="text-red-500">*</span>
                    </label>
                    <select name="mail_encryption" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="tls" {{ old('mail_encryption', $emailSettings->where('key', 'mail_encryption')->first()->value ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ old('mail_encryption', $emailSettings->where('key', 'mail_encryption')->first()->value ?? 'tls') == 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </div>

                <!-- From Address -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email gửi đi <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="mail_from_address" 
                           value="{{ old('mail_from_address', $emailSettings->where('key', 'mail_from_address')->first()->value ?? 'noreply@minierp.com') }}" 
                           required
                           placeholder="noreply@minierp.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- From Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tên người gửi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="mail_from_name" 
                           value="{{ old('mail_from_name', $emailSettings->where('key', 'mail_from_name')->first()->value ?? 'Mini ERP') }}" 
                           required
                           placeholder="Mini ERP"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <!-- Help Box -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="font-medium text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>
                    Hướng dẫn cấu hình Gmail
                </h4>
                <ol class="text-sm text-blue-800 space-y-1 ml-5 list-decimal">
                    <li>Vào <a href="https://myaccount.google.com/security" target="_blank" class="underline">Google Account Security</a></li>
                    <li>Bật "2-Step Verification" (Xác minh 2 bước)</li>
                    <li>Vào "App passwords" (Mật khẩu ứng dụng)</li>
                    <li>Chọn "Mail" và "Other", nhập tên "Mini ERP"</li>
                    <li>Copy mật khẩu 16 ký tự và dán vào trường Password ở trên</li>
                </ol>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Lưu cài đặt
                </button>
            </div>
        </form>
    </div>

    <!-- Test Email -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-paper-plane mr-2 text-green-600"></i>
                Test gửi Email
            </h3>
            <p class="text-sm text-gray-600 mt-1">Gửi email test để kiểm tra cấu hình</p>
        </div>

        <form action="{{ route('settings.email.test') }}" method="POST" class="p-6">
            @csrf
            
            <div class="max-w-md">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email nhận test <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <input type="email" name="test_email" 
                           placeholder="test@example.com"
                           required
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Gửi test
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Email test sẽ được gửi đến địa chỉ này để kiểm tra cấu hình SMTP
                </p>
            </div>
        </form>
    </div>

    <!-- Quick Setup Templates -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-magic mr-2 text-purple-600"></i>
                Cấu hình nhanh
            </h3>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Gmail Template -->
            <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition-colors">
                <h4 class="font-medium text-gray-900 mb-2">
                    <i class="fab fa-google text-red-500 mr-2"></i>
                    Gmail
                </h4>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><strong>Host:</strong> smtp.gmail.com</p>
                    <p><strong>Port:</strong> 587</p>
                    <p><strong>Encryption:</strong> TLS</p>
                </div>
            </div>

            <!-- Outlook Template -->
            <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition-colors">
                <h4 class="font-medium text-gray-900 mb-2">
                    <i class="fab fa-microsoft text-blue-500 mr-2"></i>
                    Outlook
                </h4>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><strong>Host:</strong> smtp.office365.com</p>
                    <p><strong>Port:</strong> 587</p>
                    <p><strong>Encryption:</strong> TLS</p>
                </div>
            </div>

            <!-- Mailtrap Template -->
            <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition-colors">
                <h4 class="font-medium text-gray-900 mb-2">
                    <i class="fas fa-vial text-green-500 mr-2"></i>
                    Mailtrap (Test)
                </h4>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><strong>Host:</strong> sandbox.smtp.mailtrap.io</p>
                    <p><strong>Port:</strong> 2525</p>
                    <p><strong>Encryption:</strong> TLS</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
