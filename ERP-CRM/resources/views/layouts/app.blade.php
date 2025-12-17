<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Mini ERP') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="font-sans antialiased bg-gray-100">
    <!-- Overlay for mobile sidebar -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside id="sidebar"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-sidebar text-white transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0 transition-transform duration-300 ease-in-out overflow-y-auto">
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-4 bg-secondary flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center space-x-2">
                    <i class="fas fa-cube text-primary text-2xl"></i>
                    <span class="text-xl font-bold">Mini ERP</span>
                </a>
                <button id="closeSidebar" class="lg:hidden text-white hover:text-gray-300 focus:outline-none">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="mt-4 px-2">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-primary text-white' : '' }}">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="ml-3">Dashboard</span>
                </a>

                <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Master Data</p>

                    <a href="{{ route('customers.index') }}"
                        class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('customers.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-users w-6"></i>
                        <span class="ml-3">Khách hàng</span>
                    </a>

                    <a href="{{ route('suppliers.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-truck w-6"></i>
                        <span class="ml-3">Nhà cung cấp</span>
                    </a>

                    <a href="{{ route('employees.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('employees.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-user-tie w-6"></i>
                        <span class="ml-3">Nhân viên</span>
                    </a>

                    <a href="{{ route('products.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('products.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-box w-6"></i>
                        <span class="ml-3">Sản phẩm</span>
                    </a>
                </div>

                <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Kho hàng</p>

                    <a href="{{ route('warehouses.index') }}"
                        class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('warehouses.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-warehouse w-6"></i>
                        <span class="ml-3">Quản lý kho</span>
                    </a>

                    <a href="{{ route('inventory.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('inventory.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-boxes w-6"></i>
                        <span class="ml-3">Tồn kho</span>
                    </a>

                    <a href="{{ route('imports.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('imports.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-arrow-down w-6 text-blue-400"></i>
                        <span class="ml-3">Nhập kho</span>
                    </a>

                    <a href="{{ route('exports.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('exports.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-arrow-up w-6 text-orange-400"></i>
                        <span class="ml-3">Xuất kho</span>
                    </a>

                    <a href="{{ route('transfers.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('transfers.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-exchange-alt w-6 text-purple-400"></i>
                        <span class="ml-3">Chuyển kho</span>
                    </a>

                    <a href="{{ route('damaged-goods.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('damaged-goods.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-exclamation-triangle w-6"></i>
                        <span class="ml-3">Hàng hư hỏng</span>
                    </a>
                </div>

                <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Báo cáo</p>

                    <a href="{{ route('reports.inventory-summary') }}"
                        class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.inventory-summary') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span class="ml-3">Tổng hợp tồn kho</span>
                    </a>

                    <a href="{{ route('reports.transaction-report') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.transaction-report') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-chart-line w-6"></i>
                        <span class="ml-3">Báo cáo xuất nhập</span>
                    </a>

                    <a href="{{ route('reports.damaged-goods-report') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.damaged-goods-report') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-chart-pie w-6"></i>
                        <span class="ml-3">Báo cáo hư hỏng</span>
                    </a>
                </div>

                <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Bán hàng</p>

                    <a href="{{ route('price-lists.index') }}"
                        class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('price-lists.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-tags w-6"></i>
                        <span class="ml-3">Bảng giá</span>
                    </a>

                    <a href="{{ route('quotations.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('quotations.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-file-alt w-6"></i>
                        <span class="ml-3">Báo giá</span>
                    </a>

                    <a href="{{ route('sales.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('sales.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-shopping-cart w-6"></i>
                        <span class="ml-3">Đơn hàng bán</span>
                    </a>

                    <a href="{{ route('projects.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('projects.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-project-diagram w-6 text-purple-400"></i>
                        <span class="ml-3">Quản lý dự án</span>
                    </a>

                    <a href="{{ route('customer-debts.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('customer-debts.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-file-invoice-dollar w-6"></i>
                        <span class="ml-3">Công nợ khách hàng</span>
                    </a>

                    <a href="{{ route('cost-formulas.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('cost-formulas.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-calculator w-6"></i>
                        <span class="ml-3">Công thức chi phí</span>
                    </a>
                </div>

                <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Hệ thống</p>

                    <a href="{{ route('approval-workflows.index') }}"
                        class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('approval-workflows.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-project-diagram w-6"></i>
                        <span class="ml-3">Quy trình duyệt</span>
                    </a>

                    <a href="{{ route('settings.index') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('settings.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-cog w-6"></i>
                        <span class="ml-3">Cài đặt</span>
                    </a>
                </div>
            </nav>
        </aside>


        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
                <div class="flex items-center min-w-0 flex-1">
                    <button id="openSidebar" class="lg:hidden text-gray-600 hover:text-gray-900 mr-3 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-base sm:text-lg font-semibold text-gray-800 truncate">
                        @yield('page-title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- Notifications -->
                    <button class="relative text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fas fa-bell text-lg sm:text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-danger text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">3</span>
                    </button>

                    <!-- User Menu Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-1 sm:space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none">
                            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            @auth
                                <span class="hidden sm:block font-medium text-sm">{{ Auth::user()->name }}</span>
                            @else
                                <span class="hidden sm:block font-medium text-sm">Guest</span>
                            @endauth
                            <i class="fas fa-chevron-down text-xs hidden sm:block"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200">
                            @auth
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ Auth::user()->position ?? Auth::user()->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                    <i class="fas fa-user-edit mr-2"></i>
                                    Chỉnh sửa hồ sơ
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center">
                                        <i class="fas fa-sign-out-alt mr-2"></i>
                                        Đăng xuất
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Đăng nhập
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-3 sm:p-4 lg:p-6 overflow-x-hidden min-h-0">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-3 rounded-lg relative" role="alert">
                        <span class="block sm:inline text-sm">{{ session('success') }}</span>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-3 sm:px-4 py-3 focus:outline-none" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-3 rounded-lg relative" role="alert">
                        <span class="block sm:inline text-sm">{{ session('error') }}</span>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-3 sm:px-4 py-3 focus:outline-none" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 sm:px-4 py-3 rounded-lg relative" role="alert">
                        <span class="block sm:inline text-sm">{{ session('warning') }}</span>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-3 sm:px-4 py-3 focus:outline-none" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-3 sm:py-4 px-4 sm:px-6 flex-shrink-0">
                <div class="text-center text-gray-500 text-xs sm:text-sm">
                    &copy; {{ date('Y') }} Mini ERP. All rights reserved.
                </div>
            </footer>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 flex flex-col items-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-3"></div>
            <p class="text-gray-700 font-medium">Đang xử lý...</p>
        </div>
    </div>

    <!-- Alpine.js for dropdown -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Sidebar Toggle & Interactions Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const openBtn = document.getElementById('openSidebar');
            const closeBtn = document.getElementById('closeSidebar');
            const loadingOverlay = document.getElementById('loadingOverlay');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }

            if (openBtn) openBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1024) closeSidebar();
            });

            window.confirmDelete = function (form, message = 'Bạn có chắc chắn muốn xóa?') {
                if (confirm(message)) {
                    form.submit();
                    return true;
                }
                return false;
            };

            window.showLoading = function () {
                if (loadingOverlay) loadingOverlay.classList.remove('hidden');
            };

            window.hideLoading = function () {
                if (loadingOverlay) loadingOverlay.classList.add('hidden');
            };

            document.querySelectorAll('[role="alert"]').forEach(function (alert) {
                setTimeout(function () {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function () { alert.remove(); }, 500);
                }, 5000);
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
