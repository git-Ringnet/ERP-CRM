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
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-sidebar text-white transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0 transition-transform duration-300 ease-in-out overflow-y-auto">
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
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-primary text-white' : '' }}">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="ml-3">Dashboard</span>
                </a>

                <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Master Data</p>
                    
                    <a href="{{ route('customers.index') }}" class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('customers.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-users w-6"></i>
                        <span class="ml-3">Khách hàng</span>
                    </a>

                    <a href="{{ route('suppliers.index') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-truck w-6"></i>
                        <span class="ml-3">Nhà cung cấp</span>
                    </a>

                    <a href="{{ route('employees.index') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('employees.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-user-tie w-6"></i>
                        <span class="ml-3">Nhân viên</span>
                    </a>

                    <a href="{{ route('products.index') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('products.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-box w-6"></i>
                        <span class="ml-3">Sản phẩm</span>
                    </a>
                </div>

                <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Kho hàng</p>
                    
                    <a href="{{ route('warehouses.index') }}" class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('warehouses.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-warehouse w-6"></i>
                        <span class="ml-3">Quản lý kho</span>
                    </a>

                    <a href="{{ route('inventory.index') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('inventory.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-boxes w-6"></i>
                        <span class="ml-3">Tồn kho</span>
                    </a>

                    <a href="{{ route('transactions.index') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('transactions.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-exchange-alt w-6"></i>
                        <span class="ml-3">Xuất nhập kho</span>
                    </a>

                    <a href="{{ route('damaged-goods.index') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('damaged-goods.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-exclamation-triangle w-6"></i>
                        <span class="ml-3">Hàng hư hỏng</span>
                    </a>
                </div>

                <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Báo cáo</p>
                    
                    <a href="{{ route('reports.inventory-summary') }}" class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.inventory-summary') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span class="ml-3">Tổng hợp tồn kho</span>
                    </a>

                    <a href="{{ route('reports.transaction-report') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.transaction-report') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-chart-line w-6"></i>
                        <span class="ml-3">Báo cáo xuất nhập</span>
                    </a>

                    <a href="{{ route('reports.damaged-goods-report') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.damaged-goods-report') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-chart-pie w-6"></i>
                        <span class="ml-3">Báo cáo hư hỏng</span>
                    </a>
                </div>

                <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Tiện ích</p>
                    
                    <a href="{{ route('import.index') }}" class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('import.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-file-import w-6"></i>
                        <span class="ml-3">Import dữ liệu</span>
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
                    <h1 class="text-base sm:text-lg font-semibold text-gray-800 truncate">@yield('page-title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- Search -->
                    <div class="hidden xl:block relative">
                        <input type="text" placeholder="Tìm kiếm..." class="w-48 xl:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>

                    <!-- Notifications -->
                    <button class="relative text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fas fa-bell text-lg sm:text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-danger text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">3</span>
                    </button>

                    <!-- User Menu -->
                    <div class="relative">
                        <button class="flex items-center space-x-1 sm:space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none">
                            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            <span class="hidden sm:block font-medium text-sm">Admin</span>
                            <i class="fas fa-chevron-down text-xs hidden sm:block"></i>
                        </button>
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

    <!-- Sidebar Toggle & Interactions Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const openBtn = document.getElementById('openSidebar');
            const closeBtn = document.getElementById('closeSidebar');
            const loadingOverlay = document.getElementById('loadingOverlay');

            // Sidebar functions
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

            if (openBtn) {
                openBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openSidebar();
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    closeSidebar();
                });
            }

            // Close sidebar when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    closeSidebar();
                });
            }

            // Close sidebar on window resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    closeSidebar();
                }
            });

            // Enhanced delete confirmation
            window.confirmDelete = function(form, message = 'Bạn có chắc chắn muốn xóa?') {
                if (confirm(message)) {
                    showLoading();
                    form.submit();
                    return true;
                }
                return false;
            };

            // Handle delete buttons with data attributes
            document.querySelectorAll('.delete-btn').forEach(function(btn) {
                btn.closest('form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const itemName = btn.dataset.name || 'mục này';
                    if (confirm(`Bạn có chắc chắn muốn xóa "${itemName}"?\n\nHành động này không thể hoàn tác.`)) {
                        showLoading();
                        this.submit();
                    }
                });
            });

            // Show loading overlay
            window.showLoading = function() {
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('hidden');
                }
            };

            // Hide loading overlay
            window.hideLoading = function() {
                if (loadingOverlay) {
                    loadingOverlay.classList.add('hidden');
                }
            };

            // Show loading on form submissions (except delete forms)
            document.querySelectorAll('form:not(.delete-form)').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    // Check if form has validation errors
                    const hasErrors = form.querySelector('.text-red-600, .border-red-500');
                    if (!hasErrors) {
                        showLoading();
                    }
                });
            });

            // Show loading on navigation links (except same page)
            document.querySelectorAll('a[href]:not([href^="#"]):not([target="_blank"])').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    // Don't show loading for delete buttons or external links
                    if (!this.closest('.delete-form') && !this.hasAttribute('download')) {
                        const currentPath = window.location.pathname;
                        const linkPath = new URL(this.href, window.location.origin).pathname;
                        if (currentPath !== linkPath) {
                            showLoading();
                        }
                    }
                });
            });

            // Hide loading on page load
            window.addEventListener('load', function() {
                hideLoading();
            });

            // Auto-dismiss flash messages after 5 seconds
            document.querySelectorAll('[role="alert"]').forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });

            // Add smooth scroll behavior
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
