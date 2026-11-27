<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Đăng nhập - {{ config('app.name', 'Mini ERP') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .login-bg {
            background-color: #0f1a3d;
            background-image: radial-gradient(ellipse 70% 55% at 50% 12%, #1e40af 0%, #172a6b 45%, #0f1a3d 80%, #0b1330 100%);
        }
        .login-input {
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .login-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
        }
        .login-input.is-invalid {
            border-color: #f87171;
        }
        .login-input.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(248, 113, 113, .18);
        }
        .login-btn {
            background-image: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%);
            box-shadow: 0 8px 20px -6px rgba(37, 99, 235, .55);
            transition: filter .15s ease, box-shadow .15s ease, transform .05s ease;
        }
        .login-btn:hover { filter: brightness(1.06); box-shadow: 0 10px 24px -6px rgba(37, 99, 235, .65); }
        .login-btn:active { transform: translateY(1px); }
    </style>
</head>
<body class="font-sans antialiased login-bg min-h-screen">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
        <div class="w-full max-w-[528px]">
            <!-- Login Card -->
            <div class="bg-white rounded-3xl shadow-2xl px-6 sm:px-8 pt-10 pb-8">
                <!-- Logo & Title -->
                <div class="text-center mb-7">
                    <img src="{{ asset('images/tech-horizon-logo.png') }}" alt="Tech Horizon" class="h-12 w-auto mx-auto">
                    <p class="mt-5 text-[15px] font-medium text-slate-600">Hệ thống quản lý doanh nghiệp Tech Horizon</p>
                    <span class="block w-12 h-1 mx-auto mt-4 rounded-full bg-blue-500"></span>
                </div>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-5 p-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="flex items-center gap-2 text-[15px] font-semibold text-slate-700 mb-2">
                            <i class="fas fa-envelope text-slate-500 w-4"></i>Email
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                               class="login-input w-full h-[52px] px-4 bg-white border border-gray-300 rounded-xl text-[15px] text-slate-800 placeholder-slate-500 @error('email') is-invalid @enderror"
                               placeholder="Nhập email của bạn">
                        @error('email')
                            <p class="mt-2 flex items-center gap-1.5 text-sm text-red-500">
                                <i class="fas fa-circle-exclamation"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="flex items-center gap-2 text-[15px] font-semibold text-slate-700 mb-2">
                            <i class="fas fa-lock text-slate-500 w-4"></i>Mật khẩu
                        </label>
                        <div class="relative">
                            <input id="password" type="password" name="password" required autocomplete="current-password"
                                   class="login-input w-full h-[52px] pl-4 pr-12 bg-white border border-gray-300 rounded-xl text-[15px] text-slate-800 placeholder-slate-500 @error('password') is-invalid @enderror"
                                   placeholder="Nhập mật khẩu">
                            <button type="button" onclick="togglePassword()" aria-label="Hiện/ẩn mật khẩu"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i id="toggleIcon" class="far fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 flex items-center gap-1.5 text-sm text-red-500">
                                <i class="fas fa-circle-exclamation"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center pt-1">
                        <label for="remember_me" class="inline-flex items-center cursor-pointer select-none">
                            <input id="remember_me" type="checkbox" name="remember"
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-slate-600">Ghi nhớ đăng nhập</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="login-btn w-full h-[52px] mt-2 text-white text-base font-semibold rounded-xl flex items-center justify-center gap-2.5">
                        <i class="fas fa-right-to-bracket"></i>
                        Đăng nhập
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <p class="text-center text-slate-400 text-sm mt-8">
                &copy; {{ date('Y') }} Mini ERP. All rights reserved.
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            const show = passwordInput.type === 'password';
            passwordInput.type = show ? 'text' : 'password';
            toggleIcon.classList.toggle('fa-eye', !show);
            toggleIcon.classList.toggle('fa-eye-slash', show);
        }
    </script>
</body>
</html>
