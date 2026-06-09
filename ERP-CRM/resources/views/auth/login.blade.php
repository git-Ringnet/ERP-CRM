<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Đăng nhập - {{ config('app.name', 'Mini ERP') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-900 via-blue-900 to-blue-800 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md" 
             x-data="{ tab: (new URLSearchParams(window.location.search)).get('tab') || 'login', showSuccessModal: false }"
             @registration-success.window="showSuccessModal = true; tab = 'login'">
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl mb-4 shadow-lg">
                    <i class="fas fa-cube text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white">Mini ERP</h1>
                <p class="text-gray-300 mt-2">Hệ thống quản lý doanh nghiệp</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <h2 class="text-2xl font-semibold text-gray-800 text-center mb-6" x-text="tab === 'login' ? 'Đăng nhập' : 'Đăng ký'">Đăng nhập</h2>

                <!-- Login Form Container -->
                <div x-show="tab === 'login'">
                    <!-- Session Status -->
                    @if (session('status'))
                        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                    <!-- Email Address -->
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-gray-400"></i>Email
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors @error('email') border-red-500 @enderror"
                               placeholder="Nhập email của bạn">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-gray-400"></i>Mật khẩu
                        </label>
                        <div class="relative">
                            <input id="password" type="password" name="password" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors @error('password') border-red-500 @enderror"
                                   placeholder="Nhập mật khẩu">
                            <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i id="toggleIcon" class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between mb-6">
                        <label for="remember_me" class="inline-flex items-center cursor-pointer">
                            <input id="remember_me" type="checkbox" name="remember"
                                   class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-600">Ghi nhớ đăng nhập</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Đăng nhập
                    </button>
                </form>

                @if (Route::has('register'))
                    <p class="mt-6 text-center text-sm text-gray-600">
                        Chưa có tài khoản? 
                        <a href="#" @click.prevent="tab = 'register'" class="text-primary hover:text-primary-dark font-semibold">Đăng ký ngay</a>
                    </p>
                @endif
            </div>

            <!-- Register Form Container -->
            <div x-show="tab === 'register'" style="display: none;">
                <form id="register-form" class="space-y-4">
                    <!-- Name (Username) -->
                    <div>
                        <x-input-label for="username" class="block text-sm font-medium text-gray-700 mb-2" :value="__('Tên đăng nhập')" />
                        <x-text-input id="username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary block mt-1" type="text" name="username" required
                            autocomplete="name" placeholder="Nhập tên đăng nhập" />
                    </div>

                    <!-- Email Address -->
                    <div class="mt-4">
                        <x-input-label for="register_email" class="block text-sm font-medium text-gray-700 mb-2" :value="__('Email')" />
                        <x-text-input id="register_email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary block mt-1" type="email" name="email" required
                            autocomplete="username" placeholder="Nhập địa chỉ email" />
                    </div>

                    <!-- Phone Number -->
                    <div class="mt-4">
                        <x-input-label for="phone" class="block text-sm font-medium text-gray-700 mb-2" :value="__('Số điện thoại')" />
                        <x-text-input id="phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary block mt-1" type="tel" name="phone" required
                            autocomplete="tel" placeholder="Nhập số điện thoại" />
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button class="w-full justify-center py-3 bg-primary hover:bg-primary-dark text-white font-semibold transition-all duration-200 shadow-lg uppercase text-sm">
                            Đăng ký
                        </x-primary-button>
                    </div>

                    <!-- Toggle Link -->
                    <div class="mt-6 pt-6 border-t border-gray-100 text-center text-sm text-gray-600">
                        Đã có tài khoản?
                        <a href="#" id="toggle-to-login" @click.prevent="tab = 'login'"
                            class="font-semibold text-primary hover:text-primary-dark underline focus:outline-none">
                            Đăng nhập tại đây
                        </a>
                    </div>
                </form>
            </div>
        </div>

            <!-- Footer -->
            <p class="text-center text-gray-400 text-sm mt-6">
                &copy; {{ date('Y') }} Mini ERP. All rights reserved.
            </p>

            <!-- Success Modal Overlay -->
            <div x-show="showSuccessModal" 
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all duration-300"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">
                
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all duration-300 border border-gray-100"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">
                    
                    <!-- Icon Success -->
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full mb-4">
                        <i class="fas fa-check-circle text-green-600 text-2xl animate-bounce"></i>
                    </div>

                    <h3 class="text-xl font-bold text-center text-gray-900 mb-2">Đăng ký thành công!</h3>
                    <p class="text-sm text-center text-gray-500 mb-6">Tài khoản của bạn đã được ghi nhận. Vui lòng sử dụng thông tin dùng thử sau để trải nghiệm hệ thống:</p>

                    <!-- Credentials Card -->
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-4 mb-6 border border-blue-100/50 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/5 rounded-full -mr-8 -mt-8"></div>
                        
                        <div class="space-y-3 relative z-10 text-sm">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200/50">
                                <span class="font-medium text-gray-500">Tài khoản chính (Admin):</span>
                                <span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">Khuyên dùng</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Email:</span>
                                <span class="font-mono text-gray-900 font-bold">admin@demo.com</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Mật khẩu:</span>
                                <span class="font-mono text-gray-900 font-bold">123456</span>
                            </div>
                        </div>
                    </div>

                    <!-- Other Demo Accounts Info -->
                    <div class="text-xs text-gray-500 mb-6 bg-gray-50 p-3 rounded-lg border border-gray-100 text-left">
                        <p class="font-semibold mb-1 text-gray-700">Các tài khoản demo khác (Mật khẩu: 123456):</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Kế toán: <span class="font-mono">ketoan@demo.com</span></li>
                            <li>Quản kho: <span class="font-mono">quankho@demo.com</span></li>
                            <li>Bảo hành: <span class="font-mono">baohanh@demo.com</span></li>
                        </ul>
                    </div>

                    <!-- Action Button -->
                    <button @click="showSuccessModal = false" 
                            class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary-dark transition-all duration-200 shadow-lg flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập trải nghiệm ngay
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            const CONFIG = {
                hubUrl: "https://portal.app.ringnet.vn/api/demo-register",
                siteName: "Mini ERP"
            };

            const registerForm = document.getElementById("register-form");
            if (!registerForm) return;

            registerForm.addEventListener("submit", async (e) => {
                // 1. Ngăn chặn hành vi gửi form tải lại trang ban đầu
                e.preventDefault();

                const usernameInput = registerForm.querySelector('input[name="username"]') || registerForm.querySelector('#username') || registerForm.querySelector('input[name="name"]');
                const emailInput = registerForm.querySelector('input[name="email"]') || registerForm.querySelector('#register_email');
                const phoneInput = registerForm.querySelector('input[name="phone"]') || registerForm.querySelector('input[name="tel"]') || registerForm.querySelector('#phone');
                const submitButton = registerForm.querySelector('button[type="submit"]') || registerForm.querySelector('input[type="submit"]');

                if (!usernameInput || !emailInput || !phoneInput) {
                    alert("Không tìm thấy các trường thông tin đăng ký (Username, Email, Phone) trong form.");
                    return;
                }

                const payload = {
                    username: usernameInput.value.trim(),
                    email: emailInput.value.trim(),
                    phone: phoneInput.value.trim(),
                    site_name: CONFIG.siteName
                };

                const originalButtonHtml = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = "Đang xử lý...";

                try {
                    // 2. Gửi thông tin đăng ký lên Hub cha để ghi nhận log quan tâm
                    const response = await fetch(CONFIG.hubUrl, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json"
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (response.ok) {
                        // 3. Tự động điền tài khoản demo vào form đăng nhập
                        const loginEmailInput = document.getElementById("email");
                        const loginPasswordInput = document.getElementById("password");
                        if (loginEmailInput) loginEmailInput.value = "admin@demo.com";
                        if (loginPasswordInput) loginPasswordInput.value = "123456";

                        // 4. Reset form đăng ký
                        registerForm.reset();

                        // 5. Dispatch custom event to Alpine to show modal
                        window.dispatchEvent(new CustomEvent('registration-success'));

                        // 6. Reset submit button state
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonHtml;
                    } else {
                        alert(`Lỗi: ${result.message || "Đăng ký thất bại"}`);
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonHtml;
                    }
                } catch (error) {
                    console.error(error);
                    alert("Không thể kết nối đến hệ thống Hub trung tâm.");
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHtml;
                }
            });
        });
    </script>
</body>
</html>
