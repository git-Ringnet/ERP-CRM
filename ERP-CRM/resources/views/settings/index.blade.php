@extends('layouts.app')

@section('title', 'Cài đặt hệ thống')
@section('page-title', 'Cài đặt hệ thống')

@section('content')
<div class="space-y-6">
    <!-- Company Settings -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-building mr-2 text-primary"></i>
                Thông tin Công ty
            </h3>
            <p class="text-sm text-gray-600 mt-1">Thông tin này sẽ hiển thị trên hóa đơn và các chứng từ</p>
        </div>

        <form action="{{ route('settings.company.update') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf

            {{-- Logo --}}
            <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-image mr-1 text-gray-500"></i> Logo công ty
                </label>
                <div class="flex items-start gap-5">
                    {{-- Preview box --}}
                    <div class="flex-shrink-0">
                        @php $logoPath = \App\Models\Setting::get('company_logo'); @endphp
                        @if($logoPath && file_exists(public_path($logoPath)))
                        <img id="logo-preview"
                             src="{{ asset($logoPath) }}"
                             alt="Logo"
                             class="h-24 object-contain border border-gray-200 rounded p-1 bg-white shadow-sm">
                        @else
                        <div id="logo-placeholder"
                             class="w-36 h-24 border-2 border-dashed border-gray-300 rounded flex flex-col items-center justify-center text-gray-400 text-xs bg-white">
                            <i class="fas fa-image text-3xl mb-1"></i>
                            <span>Chưa có logo</span>
                        </div>
                        <img id="logo-preview"
                             src=""
                             alt="Logo preview"
                             class="h-24 object-contain border border-gray-200 rounded p-1 bg-white shadow-sm hidden">
                        @endif
                    </div>

                    {{-- Input + hint --}}
                    <div class="flex-1">
                        <input type="file"
                               id="logo-input"
                               name="company_logo"
                               accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                               class="block w-full text-sm text-gray-600
                                      file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0
                                      file:text-sm file:font-medium file:bg-primary file:text-white
                                      hover:file:bg-primary-dark cursor-pointer mb-2">
                        <p class="text-xs text-gray-500">PNG, JPG hoặc SVG. Tối đa 2MB. Để trống nếu không muốn thay đổi.</p>
                        <p id="logo-filename" class="text-xs text-purple-600 font-medium mt-1 hidden">
                            <i class="fas fa-check-circle mr-1"></i><span></span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Live preview script --}}
            <script>
                document.getElementById('logo-input').addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const preview   = document.getElementById('logo-preview');
                    const placeholder = document.getElementById('logo-placeholder');
                    const fileLabel = document.getElementById('logo-filename');

                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        preview.src = ev.target.result;
                        preview.classList.remove('hidden');
                        if (placeholder) placeholder.classList.add('hidden');
                    };
                    reader.readAsDataURL(file);

                    // Show filename
                    if (fileLabel) {
                        fileLabel.classList.remove('hidden');
                        fileLabel.querySelector('span').textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
                    }
                });
            </script>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Company Name -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên Công ty <span class="text-red-500">*</span></label>
                    <input type="text" name="company_name"
                           value="{{ old('company_name', $companySettings->where('key', 'company_name')->first()->value ?? 'CÔNG TY TNHH RINGNET') }}"
                           required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ <span class="text-red-500">*</span></label>
                    <input type="text" name="company_address"
                           value="{{ old('company_address', $companySettings->where('key', 'company_address')->first()->value ?? '') }}"
                           required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Tax Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã số thuế</label>
                    <input type="text" name="company_tax_code"
                           value="{{ old('company_tax_code', $companySettings->where('key', 'company_tax_code')->first()->value ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                    <input type="text" name="company_phone"
                           value="{{ old('company_phone', $companySettings->where('key', 'company_phone')->first()->value ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Fax -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fax</label>
                    <input type="text" name="company_fax"
                           value="{{ old('company_fax', $companySettings->where('key', 'company_fax')->first()->value ?? '') }}"
                           placeholder="028 1234 5678"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Website -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                    <input type="text" name="company_website"
                           value="{{ old('company_website', $companySettings->where('key', 'company_website')->first()->value ?? '') }}"
                           placeholder="www.example.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Email công ty -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email công ty</label>
                    <input type="email" name="company_email"
                           value="{{ old('company_email', $companySettings->where('key', 'company_email')->first()->value ?? '') }}"
                           placeholder="info@company.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Số tài khoản ngân hàng -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số tài khoản ngân hàng</label>
                    <input type="text" name="company_bank_account"
                           value="{{ old('company_bank_account', $companySettings->where('key', 'company_bank_account')->first()->value ?? '') }}"
                           placeholder="VD: 1234567890 tại Ngân hàng TMCP Quân Đội (MB)"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i> Lưu thông tin công ty
                </button>
            </div>
        </form>
    </div>

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
