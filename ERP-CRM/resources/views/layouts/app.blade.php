<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google" content="notranslate">
    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
        <meta name="reverb-key" content="{{ config('reverb.apps.apps.0.key', env('REVERB_APP_KEY')) }}">
        <meta name="reverb-host" content="{{ env('REVERB_HOST', request()->getHost()) }}">
        <meta name="reverb-port" content="{{ env('REVERB_PORT', request()->isSecure() ? 443 : 8080) }}">
        <meta name="reverb-scheme" content="{{ env('REVERB_SCHEME', request()->isSecure() ? 'https' : 'http') }}">
    @endauth

    <title>@yield('title', 'Mini ERP') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap"
        rel="stylesheet">

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts and Base Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Flatpickr (Loaded after app.css to prevent Tailwind Forms base override) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>

    <!-- Chart.js CDN - Load after Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>

    <style>
        /* Sidebar collapsed state - hide text, show only icons */
        .sidebar-collapsed .sidebar-text {
            display: none;
        }

        .sidebar-collapsed nav a,
        .sidebar-collapsed nav div {
            justify-content: center;
        }

        .sidebar-collapsed #sidebarHeader {
            justify-content: center;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        /* Dropdown menu styles */
        .dropdown-section {
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }

        .dropdown-section.collapsed {
            max-height: 0;
        }

        .dropdown-arrow {
            transition: transform 0.3s ease-in-out;
        }

        .dropdown-arrow.rotated {
            transform: rotate(180deg);
        }

        .section-header {
            cursor: pointer;
            user-select: none;
        }

        .section-header:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }

        /* Hiệu ứng nhấp nháy nhẹ nhàng cho badge thông báo */
        @keyframes gentle-pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5);
            }

            50% {
                transform: scale(1.06);
                opacity: 0.88;
                box-shadow: 0 0 7px 2px rgba(239, 68, 68, 0.4);
            }
        }

        [data-sidebar-badge],
        .notification-badge-pulse {
            animation: gentle-pulse 2.2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            will-change: transform, opacity;
        }
    </style>

    @stack('styles')
</head>

<body class="font-sans antialiased bg-gray-100">
    <!-- Overlay for mobile sidebar -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside id="sidebar"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-sidebar text-white transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0 transition-all duration-300 ease-in-out overflow-y-auto lg:w-64">
            <!-- Logo -->
            <div id="sidebarHeader" class="flex items-center justify-between h-16 px-4 bg-secondary flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center space-x-2 sidebar-text">
                    <i class="fas fa-cube text-primary text-2xl"></i>
                    <span class="text-xl font-bold whitespace-nowrap">Mini ERP</span>
                </a>
                <div class="flex items-center space-x-2">
                    <button id="toggleSidebar" class="text-white hover:text-gray-300 focus:outline-none flex-shrink-0">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <button id="closeSidebar" class="lg:hidden text-white hover:text-gray-300 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="mt-4 px-2">
                @can('view_dashboard')
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('dashboard*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-tachometer-alt w-6 flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text whitespace-nowrap">Dashboard</span>
                    </a>
                @endcan

                {{-- <div class="mt-4">
                    <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                        onclick="toggleDropdown('personal')">
                        <div class="flex items-center">
                            <i class="fas fa-user-circle w-6 text-pink-400 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Cá nhân</span>
                        </div>
                        <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-personal"></i>
                    </div>

                    <div class="dropdown-section" id="dropdown-personal">
                        <a href="{{ route('attendance.index') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('attendance.*') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-map-marker-alt w-6 text-green-400 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Chấm công</span>
                        </a>
                        <a href="{{ route('work-locations.index') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('work-locations.*') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-map-marker-alt w-6 text-red-400 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Địa điểm làm việc</span>
                        </a>
                    </div>
                </div> --}}

                @canany(['view_customers', 'view_suppliers', 'view_employees', 'view_products'])
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors cursor-pointer"
                            onclick="toggleDropdown('masterData')">
                            <div class="flex items-center">
                                <i class="fas fa-database w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Master Data</span>
                            </div>
                            <div class="flex items-center sidebar-text space-x-2">
                                <i class="fas fa-chevron-down dropdown-arrow" id="arrow-masterData"></i>
                            </div>
                        </div>

                        <div class="dropdown-section" id="dropdown-masterData">
                            @can('view_customers')
                                <a href="{{ route('customers.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('customers.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-users w-6 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Khách hàng</span>
                                    </div>
                                </a>
                            @endcan

                            @can('view_suppliers')
                                <a href="{{ route('suppliers.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-truck w-6 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Nhà cung cấp</span>
                                    </div>
                                </a>
                            @endcan

                            @can('view_employees')
                                <a href="{{ route('employees.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('employees.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-user-tie w-6 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Nhân viên</span>
                                    </div>
                                </a>
                            @endcan

                            @can('view_products')
                                <a href="{{ route('products.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('products.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-box w-6 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Sản phẩm</span>
                                    </div>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @canany(['view_warehouses', 'view_inventory', 'view_imports', 'view_exports', 'view_all_exports', 'view_own_exports', 'view_transfers', 'view_damaged_goods'])
                <div class="mt-4">
                    <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors cursor-pointer"
                        onclick="toggleDropdown('warehouse')">
                        <div class="flex items-center">
                            <i class="fas fa-warehouse w-6 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Kho hàng</span>
                        </div>
                        <div class="flex items-center sidebar-text space-x-2">
                            <span data-sidebar-badge="warehouse_total"
                                class="{{ empty($sidebarBadges['warehouse_total']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                {{ $sidebarBadges['warehouse_total'] ?? '' }}
                            </span>
                            <i class="fas fa-chevron-down dropdown-arrow" id="arrow-warehouse"></i>
                        </div>
                    </div>

                    <div class="dropdown-section" id="dropdown-warehouse">
                        @can('view_warehouses')
                            <a href="{{ route('warehouses.index') }}"
                                class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('warehouses.*') ? 'bg-primary text-white' : '' }}">
                                <div class="flex items-center min-w-0">
                                    <i class="fas fa-warehouse w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Quản lý kho</span>
                                </div>
                                <span data-sidebar-badge="warehouses"
                                    class="sidebar-text ml-auto {{ empty($sidebarBadges['warehouses']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                    {{ $sidebarBadges['warehouses'] ?? '' }}
                                </span>
                            </a>
                        @endcan

                        @can('view_inventory')
                            <a href="{{ route('inventory.index') }}"
                                class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('inventory.*') ? 'bg-primary text-white' : '' }}">
                                <div class="flex items-center min-w-0">
                                    <i class="fas fa-boxes w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Tồn kho</span>
                                </div>
                                <span data-sidebar-badge="inventory"
                                    class="sidebar-text ml-auto {{ empty($sidebarBadges['inventory']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                    {{ $sidebarBadges['inventory'] ?? '' }}
                                </span>
                            </a>
                        @endcan

                        @can('view_imports')
                            <a href="{{ route('imports.index') }}"
                                class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('imports.*') ? 'bg-primary text-white' : '' }}">
                                <div class="flex items-center min-w-0">
                                    <i class="fas fa-arrow-down w-6 text-blue-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Nhập kho</span>
                                </div>
                                <span data-sidebar-badge="imports"
                                    class="sidebar-text ml-auto {{ empty($sidebarBadges['imports']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                    {{ $sidebarBadges['imports'] ?? '' }}
                                </span>
                            </a>
                        @endcan

                        @canany(['view_exports', 'view_all_exports', 'view_own_exports'])
                                    <a href="{{ route('exports.index') }}"
                                        class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('exports.*') ? 'bg-primary text-white' : '' }}">
                                        <div class="flex items-center min-w-0">
                                            <i class="fas fa-arrow-up w-6 text-orange-400 flex-shrink-0"></i>
                                            <span class="ml-3 sidebar-text whitespace-nowrap">Xuất kho</span>
                                        </div>
                                        <span data-sidebar-badge="exports"
                                            class="sidebar-text ml-auto {{ empty($sidebarBadges['exports']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                            {{ $sidebarBadges['exports'] ?? '' }}
                                        </span>
                                    </a>
                                    @endcan

                                    @can('view_transfers')
                                        <a href="{{ route('transfers.index') }}"
                                            class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('transfers.*') ? 'bg-primary text-white' : '' }}">
                                            <div class="flex items-center min-w-0">
                                                <i class="fas fa-exchange-alt w-6 text-purple-400 flex-shrink-0"></i>
                                                <span class="ml-3 sidebar-text whitespace-nowrap">Chuyển kho</span>
                                            </div>
                                            <span data-sidebar-badge="transfers"
                                                class="sidebar-text ml-auto {{ empty($sidebarBadges['transfers']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                                {{ $sidebarBadges['transfers'] ?? '' }}
                                            </span>
                                        </a>
                                    @endcan

                                    @can('view_damaged_goods')
                                        <a href="{{ route('damaged-goods.index') }}"
                                            class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('damaged-goods.*') ? 'bg-primary text-white' : '' }}">
                                            <div class="flex items-center min-w-0">
                                                <i class="fas fa-exclamation-triangle w-6 flex-shrink-0"></i>
                                                <span class="ml-3 sidebar-text whitespace-nowrap">Hàng hư hỏng</span>
                                            </div>
                                            <span data-sidebar-badge="damaged_goods"
                                                class="sidebar-text ml-auto {{ empty($sidebarBadges['damaged_goods']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                                {{ $sidebarBadges['damaged_goods'] ?? '' }}
                                            </span>
                                        </a>
                                    @endcan

                                    <a href="{{ route('tickets.index') }}"
                                        class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('tickets.*') ? 'bg-primary text-white' : '' }}">
                                        <div class="flex items-center min-w-0">
                                            <i class="fas fa-ticket-alt w-6 text-teal-400 flex-shrink-0"></i>
                                            <span class="ml-3 sidebar-text whitespace-nowrap">Yêu cầu (Ticket)</span>
                                        </div>
                                        <span data-sidebar-badge="tickets"
                                            class="sidebar-text ml-auto {{ empty($sidebarBadges['tickets']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                            {{ $sidebarBadges['tickets'] ?? '' }}
                                        </span>
                                    </a>
                                </div>
                            </div>
                        @endcanany

                {{-- @can('view_reports')
                <div class="mt-4">
                    <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                        onclick="toggleDropdown('reports')">
                        <div class="flex items-center">
                            <i class="fas fa-chart-bar w-6 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Báo cáo</span>
                        </div>
                        <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-reports"></i>
                    </div>

                    <div class="dropdown-section" id="dropdown-reports">
                        <a href="{{ route('reports.inventory-summary') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.inventory-summary') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-chart-bar w-6 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Tổng hợp tồn kho</span>
                        </a>

                        <a href="{{ route('reports.transaction-report') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.transaction-report') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-chart-line w-6 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Báo cáo xuất nhập</span>
                        </a>

                        <a href="{{ route('reports.damaged-goods-report') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.damaged-goods-report') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-chart-pie w-6 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Báo cáo hư hỏng</span>
                        </a>

                        <a href="{{ route('warranties.index') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('warranties.*') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-shield-alt w-6 text-green-400 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Theo dõi bảo hành</span>
                        </a>
                    </div>
                </div>
                @endcan --}}

                {{-- <div class="mt-4">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider sidebar-text">Lịch biểu
                    </p>

                    <a href="{{ route('work-schedules.index') }}"
                        class="flex items-center px-4 py-3 mt-2 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('work-schedules.*') ? 'bg-primary text-white' : '' }}">
                        <i class="fas fa-calendar-alt w-6 flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text whitespace-nowrap">Lịch làm việc</span>
                    </a>
                </div> --}}

                @if(false)
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                            onclick="toggleDropdown('assets')">
                            <div class="flex items-center">
                                <i class="fas fa-laptop w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Tài sản nội bộ</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-assets"></i>
                        </div>

                        <div class="dropdown-section" id="dropdown-assets">
                            <a href="{{ route('employee-assets.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('employee-assets.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-box w-6 text-indigo-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Danh mục tài sản</span>
                            </a>
                            <a href="{{ route('employee-asset-assignments.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('employee-asset-assignments.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-exchange-alt w-6 text-teal-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Cấp phát & Thu hồi</span>
                            </a>
                            <a href="{{ route('employee-asset-reports.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('employee-asset-reports.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-chart-pie w-6 text-pink-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Báo cáo tổng hợp</span>
                            </a>
                            <a href="{{ route('skills.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('skills.*') || request()->routeIs('employee-skills.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-graduation-cap w-6 text-yellow-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Quản lý Kỹ năng</span>
                            </a>
                        </div>
                    </div>
                @endif

                @if(false)
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                            onclick="toggleDropdown('kpis')">
                            <div class="flex items-center">
                                <i class="fas fa-star w-6 text-yellow-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Đánh giá & KPI</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-kpis"></i>
                        </div>

                        <div class="dropdown-section" id="dropdown-kpis">
                            <a href="{{ route('department-kpis.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('department-kpis.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-chart-line w-6 text-pink-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Kỳ đánh giá KPI</span>
                            </a>
                            <a href="{{ route('department-kpi-criteria.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('department-kpi-criteria.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-list-check w-6 text-green-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Tiêu chí chuẩn</span>
                            </a>

                            <a href="{{ route('employee-sales-revenues.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('employee-sales-revenues.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-money-bill-trend-up w-6 text-cyan-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Ghi nhận doanh số</span>
                            </a>
                        </div>
                    </div>
                @endif

                @if(false)
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                            onclick="toggleDropdown('hr_payroll')">
                            <div class="flex items-center">
                                <i class="fas fa-users-cog w-6 text-indigo-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Nhân sự & Tiền lương</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-hr_payroll"></i>
                        </div>

                        <div class="dropdown-section" id="dropdown-hr_payroll">
                            <a href="{{ route('salary-components.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('salary-components.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-list-ul w-6 text-orange-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Danh mục Phụ cấp</span>
                            </a>
                            <a href="{{ route('attendance.manage') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('attendance.manage') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-clipboard-check w-6 text-green-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Lịch sử Chấm công</span>
                            </a>
                            <a href="{{ route('payrolls.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('payrolls.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-money-check-alt w-6 text-yellow-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Bảng lương</span>
                            </a>
                        </div>
                    </div>
                @endif

                @canany(['view_leads', 'view_opportunities', 'view_activities', 'view_customer_care_stages', 'view_quotations', 'view_sales', 'view_projects', 'view_customer_debts', 'view_cost_formulas', 'view_sale_reports', 'view_marketing_events', 'view_sales_revenues'])
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors cursor-pointer"
                            onclick="toggleDropdown('sales')">
                            <div class="flex items-center">
                                <i class="fas fa-shopping-cart w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Bán hàng</span>
                            </div>
                            <div class="flex items-center sidebar-text space-x-2">
                                <span data-sidebar-badge="sales_total"
                                    class="{{ empty($sidebarBadges['sales_total']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                    {{ $sidebarBadges['sales_total'] ?? '' }}
                                </span>
                                <i class="fas fa-chevron-down dropdown-arrow" id="arrow-sales"></i>
                            </div>
                        </div>

                        <div class="dropdown-section" id="dropdown-sales">
                            @can('view_opportunities')
                                <a href="{{ route('opportunities.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ (request()->routeIs('opportunities.*') && !request()->routeIs('opportunities.report')) ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-calendar-check w-6 text-blue-400 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Cơ hội</span>
                                    </div>
                                    <span data-sidebar-badge="opportunities"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['opportunities']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['opportunities'] ?? '' }}
                                    </span>
                                </a>
                            @endcan

                            @can('view_projects')
                                <a href="{{ route('projects.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('projects.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-project-diagram w-6 text-purple-400 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Đăng ký dự án</span>
                                    </div>
                                    <span data-sidebar-badge="projects"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['projects']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['projects'] ?? '' }}
                                    </span>
                                </a>
                            @endcan

                            @can('view_opportunities')
                                <a href="{{ route('opportunities.report') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('opportunities.report') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-chart-bar w-6 text-indigo-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Báo cáo tần suất gặp</span>
                                </a>
                            @endcan

                            @can('view_marketing_events')
                                <a href="{{ route('marketing-events.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('marketing-events.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-calendar-alt w-6 text-purple-400 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Sự kiện Marketing</span>
                                    </div>
                                    <span data-sidebar-badge="marketing_events"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['marketing_events']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['marketing_events'] ?? '' }}
                                    </span>
                                </a>
                                <a href="{{ route('marketing-items.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('marketing-items.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-boxes w-6 text-purple-400 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Kho vật phẩm MKT</span>
                                    </div>
                                    <span data-sidebar-badge="marketing_items"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['marketing_items']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['marketing_items'] ?? '' }}
                                    </span>
                                </a>
                            @endcan

                            <a href="{{ route('meeting-rooms.index') }}"
                                class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('meeting-rooms.*') ? 'bg-primary text-white' : '' }}">
                                <div class="flex items-center min-w-0">
                                    <i class="fas fa-door-open w-6 text-purple-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Đặt phòng họp</span>
                                </div>
                                <span data-sidebar-badge="meeting_rooms"
                                    class="sidebar-text ml-auto {{ empty($sidebarBadges['meeting_rooms']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                    {{ $sidebarBadges['meeting_rooms'] ?? '' }}
                                </span>
                            </a>

                            @can('view_quotations')
                                <a href="{{ route('quotations.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('quotations.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-file-alt w-6 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Báo giá</span>
                                    </div>
                                    <span data-sidebar-badge="quotations"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['quotations']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['quotations'] ?? '' }}
                                    </span>
                                </a>
                            @endcan

                            @can('view_sales')
                                <a href="{{ route('sales.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('sales.*') && !request()->routeIs('sales.order-tracking') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-shopping-cart w-6 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Đơn hàng bán</span>
                                    </div>
                                    <span data-sidebar-badge="sales"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['sales']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['sales'] ?? '' }}
                                    </span>
                                </a>
                            @endcan

                            @can('view_customer_debts')
                                <a href="{{ route('customer-debts.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('customer-debts.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-file-invoice-dollar w-6 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Công nợ khách hàng</span>
                                    </div>
                                    <span data-sidebar-badge="customer_debts"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['customer_debts']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['customer_debts'] ?? '' }}
                                    </span>
                                </a>
                            @endcan

                            @can('view_cost_formulas')
                                {{-- <a href="{{ route('cost-formulas.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('cost-formulas.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-calculator w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Công thức chi phí</span>
                                </a> --}}
                            @endcan

                            @can('view_sale_reports')
                                <a href="{{ route('sale-reports.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('sale-reports.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-chart-line w-6 text-pink-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Báo cáo bán hàng</span>
                                </a>
                            @endcan

                            @can('create_purchase_requests')
                                <a href="{{ route('purchase-requests.index', ['my_requests' => 1]) }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('purchase-requests.index') && request()->boolean('my_requests') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-clipboard-list w-6 text-cyan-400 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Yêu cầu đặt hàng</span>
                                    </div>
                                    <span data-sidebar-badge="purchase_requests_my"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['purchase_requests_my']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['purchase_requests_my'] ?? '' }}
                                    </span>
                                </a>
                            @endcan

                            @canany(['view_sales', 'view_all_sales', 'view_own_sales'])
                                <a href="{{ route('sales.order-tracking') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('sales.order-tracking') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-map-marked-alt w-6 text-emerald-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Theo dõi hàng về</span>
                                </a>
                            @endcanany

                            @can('view_sales_revenues')
                                <a href="{{ route('sales-revenues.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('sales-revenues.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-chart-bar w-6 text-orange-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Tổng Doanh Số</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @canany(['view_technical_tickets', 'create_technical_tickets', 'manage_technical_support_logs', 'view_technical_dashboard', 'export_technical_tickets'])
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors cursor-pointer"
                            onclick="toggleDropdown('technical')">
                            <div class="flex items-center">
                                <i class="fas fa-tools w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Technical</span>
                            </div>
                            <div class="flex items-center sidebar-text space-x-2">
                                <span data-sidebar-badge="technical_total"
                                    class="{{ empty($sidebarBadges['technical_total']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                    {{ $sidebarBadges['technical_total'] ?? '' }}
                                </span>
                                <i class="fas fa-chevron-down dropdown-arrow" id="arrow-technical"></i>
                            </div>
                        </div>

                        <div class="dropdown-section" id="dropdown-technical">
                            @can('view_technical_dashboard')
                                <a href="{{ route('technical.dashboard') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('technical.dashboard') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-chart-pie w-6 text-pink-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Báo cáo</span>
                                </a>
                            @endcan
                            @canany(['view_technical_tickets', 'manage_technical_support_logs'])
                                @can('view_technical_tickets')
                                    <a href="{{ route('technical-tickets.index') }}"
                                        class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('technical-tickets.*') ? 'bg-primary text-white' : '' }}">
                                        <div class="flex items-center min-w-0">
                                            <i class="fas fa-ticket-alt w-6 text-teal-400 flex-shrink-0"></i>
                                            <span class="ml-3 sidebar-text whitespace-nowrap">Quản lý Ticket</span>
                                        </div>
                                        <span data-sidebar-badge="technical_tickets"
                                            class="sidebar-text ml-auto {{ empty($sidebarBadges['technical_tickets']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                            {{ $sidebarBadges['technical_tickets'] ?? '' }}
                                        </span>
                                    </a>
                                @endcan
                                @can('manage_technical_support_logs')
                                    <a href="{{ route('technical.support-logs.index') }}"
                                        class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('technical.support-logs.*') ? 'bg-primary text-white' : '' }}">
                                        <div class="flex items-center min-w-0">
                                            <i class="fas fa-history w-6 text-yellow-400 flex-shrink-0"></i>
                                            <span class="ml-3 sidebar-text whitespace-nowrap">Nhật ký hỗ trợ</span>
                                        </div>
                                        <span data-sidebar-badge="technical_support_logs"
                                            class="sidebar-text ml-auto {{ empty($sidebarBadges['technical_support_logs']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                            {{ $sidebarBadges['technical_support_logs'] ?? '' }}
                                        </span>
                                    </a>
                                @endcan
                            @endcanany
                        </div>
                    </div>
                @endcanany

                @canany(['view_supplier_price_lists', 'view_purchase_requests', 'view_all_purchase_requests', 'view_supplier_quotations', 'view_purchase_orders', 'view_all_purchase_orders', 'view_own_purchase_orders', 'view_shipping_allocations', 'view_purchase_reports', 'view_pr_approvals', 'view_needs_ordering', 'create_purchase_orders', 'create_needs_ordering'])
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors cursor-pointer"
                            onclick="toggleDropdown('purchasing')">
                            <div class="flex items-center">
                                <i class="fas fa-file-contract w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Mua hàng</span>
                            </div>
                            <div class="flex items-center sidebar-text space-x-2">
                                <span data-sidebar-badge="purchasing_total"
                                    class="{{ empty($sidebarBadges['purchasing_total']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                    {{ $sidebarBadges['purchasing_total'] ?? '' }}
                                </span>
                                <i class="fas fa-chevron-down dropdown-arrow" id="arrow-purchasing"></i>
                            </div>
                        </div>

                        <div class="dropdown-section" id="dropdown-purchasing">
                            @can('view_supplier_price_lists')
                                <a href="{{ route('supplier-price-lists.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('supplier-price-lists.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-tags w-6 text-green-400"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Bảng giá</span>
                                </a>
                            @endcan

                            {{-- Ẩn RFQ - theo yêu cầu bỏ chức năng báo giá NCC
                            @can('view_purchase_requests')
                            <a href="{{ route('purchase-requests.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('purchase-requests.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-clipboard-list w-6"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Yêu cầu báo giá từ NCC</span>
                            </a>
                            @endcan

                            @can('view_supplier_quotations')
                            <a href="{{ route('supplier-quotations.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('supplier-quotations.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-file-invoice w-6"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Báo giá NCC</span>
                            </a>
                            @endcan
                            --}}

                            @can('view_pr_approvals')
                                <a href="{{ route('purchase-requests.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('purchase-requests.index') && !request()->has('my_requests') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-clipboard-check w-6 text-yellow-400"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Duyệt yêu cầu (PR)</span>
                                    </div>
                                    <span data-sidebar-badge="pr_approvals"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['pr_approvals']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['pr_approvals'] ?? '' }}
                                    </span>
                                </a>
                            @endcan

                            @canany(['view_needs_ordering', 'create_needs_ordering', 'create_purchase_orders'])
                                <a href="{{ route('purchase-requests.needs-ordering') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('purchase-requests.needs-ordering') && !request()->has('open_fast_so') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-layer-group w-6 text-teal-400"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Gom đơn cần đặt</span>
                                    </div>
                                    <span data-sidebar-badge="needs_ordering"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['needs_ordering']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['needs_ordering'] ?? '' }}
                                    </span>
                                </a>
                                <a href="{{ route('purchase-requests.needs-ordering', ['open_fast_so' => 1]) }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->boolean('open_fast_so') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-bolt w-6 text-yellow-400"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Đơn hàng (Không PNL)</span>
                                    </div>
                                </a>
                            @endcanany

                            @canany(['view_purchase_orders', 'view_all_purchase_orders', 'view_own_purchase_orders', 'create_purchase_orders'])
                                <a href="{{ route('purchase-orders.index') }}"
                                    class="flex items-center justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('purchase-orders.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-file-contract w-6 text-blue-400"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Đặt hàng với hãng (PO)</span>
                                    </div>
                                    <span data-sidebar-badge="purchase_orders"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['purchase_orders']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['purchase_orders'] ?? '' }}
                                    </span>
                                </a>
                            @endcanany

                            <!-- @can('view_shipping_allocations')
                                                                                                                <a href="{{ route('shipping-allocations.index') }}"
                                                                                                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('shipping-allocations.*') ? 'bg-primary text-white' : '' }}">
                                                                                                                    <i class="fas fa-truck-loading w-6 text-orange-400"></i>
                                                                                                                    <span class="ml-3 sidebar-text whitespace-nowrap">Phân bổ CP vận chuyển</span>
                                                                                                                </a>
                                                                                                            @endcan -->

                            @can('view_purchase_reports')
                                <a href="{{ route('purchase-reports.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('purchase-reports.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-chart-pie w-6 text-purple-400"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Báo cáo mua hàng</span>
                                </a>
                            @endcan

                            {{-- <a href="{{ route('supplier-debts.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('supplier-debts.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-file-invoice-dollar w-6 text-emerald-400"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Công nợ NCC</span>
                            </a> --}}
                        </div>
                    </div>
                @endcanany

                {{-- Quản trị Kế toán - Rút gọn theo yêu cầu --}}
                {{-- <div class="mt-4">
                    <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                        onclick="toggleDropdown('accounting')">
                        <div class="flex items-center">
                            <i class="fas fa-calculator w-6 flex-shrink-0"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Quản trị Kế toán</span>
                        </div>
                        <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-accounting"></i>
                    </div>

                    <div class="dropdown-section" id="dropdown-accounting">
                        <a href="{{ route('reports.business-overview') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.business-overview') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-list-alt w-6 flex-shrink-0 text-blue-400"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Đơn hàng Bán/Nhập</span>
                        </a>
                        <a href="{{ route('financial-transactions.index') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('financial-transactions.*') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-wallet w-6 flex-shrink-0 text-green-400"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Quản lý Thu Chi</span>
                        </a>
                        <a href="{{ route('cash-flow-report.index') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('cash-flow-report.*') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-chart-area w-6 flex-shrink-0 text-teal-400"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Báo cáo Dòng tiền</span>
                        </a>



                        <a href="{{ route('reports.balance-sheet') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reports.balance-sheet') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-balance-scale w-6 flex-shrink-0 text-purple-400"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Bảng cân đối kế toán</span>
                        </a>

                        <a href="{{ route('reconciliation.index') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('reconciliation.*') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-check-double w-6 flex-shrink-0 text-rose-400"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Đối soát</span>
                        </a>

                        <a href="{{ route('accounting.journal.index') }}"
                            class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('accounting.journal.*') ? 'bg-primary text-white' : '' }}">
                            <i class="fas fa-book w-6 flex-shrink-0 text-amber-400"></i>
                            <span class="ml-3 sidebar-text whitespace-nowrap">Nhật ký kế toán kho</span>
                        </a>
                    </div>
                </div> --}}

                @canany(['view_approval_workflows', 'view_activity_logs', 'view_settings'])
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors cursor-pointer"
                            onclick="toggleDropdown('system')">
                            <div class="flex items-center">
                                <i class="fas fa-cog w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Hệ thống</span>
                            </div>
                            <div class="flex items-center sidebar-text space-x-2">
                                <span data-sidebar-badge="system_total"
                                    class="{{ empty($sidebarBadges['system_total']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                    {{ $sidebarBadges['system_total'] ?? '' }}
                                </span>
                                <i class="fas fa-chevron-down dropdown-arrow" id="arrow-system"></i>
                            </div>
                        </div>

                        <div class="dropdown-section" id="dropdown-system">
                            @can('view_approval_workflows')
                                <a href="{{ route('approval-workflows.index') }}"
                                    class="flex items-center hidden justify-between px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('approval-workflows.*') ? 'bg-primary text-white' : '' }}">
                                    <div class="flex items-center min-w-0">
                                        <i class="fas fa-project-diagram w-6 flex-shrink-0"></i>
                                        <span class="ml-3 sidebar-text whitespace-nowrap">Quy trình duyệt</span>
                                    </div>
                                    <span data-sidebar-badge="approval_workflows"
                                        class="sidebar-text ml-auto {{ empty($sidebarBadges['approval_workflows']) ? 'hidden' : 'inline-flex' }} items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full shadow-sm">
                                        {{ $sidebarBadges['approval_workflows'] ?? '' }}
                                    </span>
                                </a>
                            @endcan

                            @can('view_activity_logs')
                                <a href="{{ route('activity-logs.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('activity-logs.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-history w-6 text-purple-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Nhật ký hoạt động</span>
                                </a>
                            @endcan

                            @can('view_settings')
                                <a href="{{ route('settings.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('settings.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-cog w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Cài đặt</span>
                                </a>
                            @endcan

                            <a href="{{ route('exchange-rates.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('exchange-rates.*') || request()->routeIs('currencies.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-coins w-6 text-yellow-500 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Tiền tệ & Tỷ giá</span>
                            </a>
                        </div>
                    </div>
                @endcanany

                @can('view_roles')
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                            onclick="toggleDropdown('access')">
                            <div class="flex items-center">
                                <i class="fas fa-user-shield w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Quản lý Truy cập</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-access"></i>
                        </div>

                        <div class="dropdown-section" id="dropdown-access">
                            @can('view_user_roles')
                                <a href="{{ route('users.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('users.index') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-users w-6 text-cyan-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Người dùng</span>
                                </a>
                            @endcan

                            @can('view_roles')
                                <a href="{{ route('user-groups.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('user-groups.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-users-cog w-6 text-emerald-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Phân nhóm (Team)</span>
                                </a>
                                <a href="{{ route('roles.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('roles.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-user-tag w-6 text-blue-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Vai trò</span>
                                </a>
                            @endcan

                            @can('view_permissions')
                                <a href="{{ route('permissions.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('permissions.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-key w-6 text-yellow-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Quyền</span>
                                </a>
                            @endcan

                            @can('view_audit_logs')
                                <a href="{{ route('audit-logs.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('audit-logs.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-clipboard-list w-6 text-red-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Nhật ký Kiểm toán</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcan

                <!-- Hướng Dẫn Sử Dụng / Help Center Item -->
                <div class="mt-4 pt-3 border-t border-gray-700 hidden">
                    <a href="{{ route('user-guide.index') }}"
                        class="flex items-center px-4 py-3 text-cyan-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all font-semibold {{ request()->routeIs('user-guide.*') ? 'bg-blue-600 text-white shadow-lg' : 'bg-slate-800 bg-opacity-40' }}">
                        <i class="fas fa-book-open w-6 text-cyan-400 flex-shrink-0 text-lg"></i>
                        <span class="ml-3 sidebar-text whitespace-nowrap">Hướng dẫn sử dụng</span>
                    </a>
                </div>
            </nav>
        </aside>


        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen min-w-0">
            <!-- Top Header -->
            <header
                class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
                <div class="flex items-center min-w-0 flex-1">
                    <h1 class="text-base sm:text-lg font-semibold text-gray-800 truncate">
                        @yield('page-title', 'Dashboard')
                    </h1>
                </div>

                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- User Guide / Help Center Button -->
                    <a href="{{ route('user-guide.index') }}"
                        class="inline-flex items-center hidden px-3 py-1.5 sm:px-3.5 sm:py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs sm:text-sm font-semibold rounded-lg border border-blue-200 shadow-sm transition-all hover:shadow"
                        title="Trung tâm Hướng dẫn & Quy trình vận hành">
                        <i class="fas fa-book-open mr-1.5 text-blue-600"></i>
                        <span class="whitespace-nowrap hidden sm:inline">Hướng dẫn sử dụng</span>
                    </a>

                    <!-- Notification Bell & Push Notification Manager -->
                    <div class="relative" x-data="notificationBell()" x-init="init()">
                        <!-- Bell Icon with Badge -->
                        <button @click="toggleDropdown()"
                            class="relative text-gray-600 hover:text-gray-900 focus:outline-none">
                            <i class="fas fa-bell text-lg sm:text-xl"></i>
                            <span x-show="unreadCount > 0" x-text="unreadCount > 99 ? '99+' : unreadCount"
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-semibold notification-badge-pulse">
                            </span>
                        </button>

                        <!-- In-App Floating Push Toast Notifications -->
                        <div class="fixed top-5 right-5 z-[99999] flex flex-col space-y-3 max-w-sm w-full pointer-events-none"
                            aria-live="assertive">
                            <template x-for="toast in pushToasts" :key="toast.id">
                                <div x-transition:enter="transform ease-out duration-300 transition"
                                    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
                                    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="pointer-events-auto bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden ring-1 ring-black/5 hover:shadow-xl transition-all duration-200">
                                    <div class="p-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 pt-0.5">
                                                <div :class="getToastIconBg(toast.color)"
                                                    class="w-9 h-9 rounded-lg flex items-center justify-center shadow-sm">
                                                    <i :class="getIconClass(toast)" class="text-base"></i>
                                                </div>
                                            </div>
                                            <div class="ml-3 w-0 flex-1">
                                                <div class="flex items-center justify-between">
                                                    <span
                                                        class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Thông
                                                        báo mới</span>
                                                    <span class="text-[10px] text-gray-400">Vừa xong</span>
                                                </div>
                                                <p class="text-sm font-bold text-gray-900 mt-0.5 leading-snug"
                                                    x-text="toast.title"></p>
                                                <p class="mt-1 text-xs text-gray-600 line-clamp-2 leading-relaxed"
                                                    x-text="toast.message"></p>
                                                <div class="mt-3 flex items-center space-x-2">
                                                    <button @click="openToast(toast)"
                                                        class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-md transition-colors">
                                                        <span>Xem ngay</span>
                                                        <i class="fas fa-arrow-right ml-1.5 text-[10px]"></i>
                                                    </button>
                                                    <button @click="dismissToast(toast.id)"
                                                        class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1">
                                                        Bỏ qua
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="ml-2 flex-shrink-0 flex">
                                                <button @click="dismissToast(toast.id)"
                                                    class="rounded-md text-gray-400 hover:text-gray-600 focus:outline-none p-1">
                                                    <span class="sr-only">Close</span>
                                                    <i class="fas fa-times text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Dropdown -->
                        <div x-show="isOpen" x-cloak @click.away="isOpen = false" x-transition
                            class="absolute right-0 mt-2 w-96 bg-white shadow-lg rounded-lg z-50 border border-gray-200">
                            <!-- Header -->
                            <div class="flex justify-between items-center p-4 border-b">
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-semibold text-gray-800">Thông báo</h3>
                                    <span x-show="desktopPermission === 'granted'"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 mr-1 bg-emerald-500 rounded-full animate-pulse"></span>
                                        Push ON
                                    </span>
                                </div>
                                <button @click="markAllAsRead()" :disabled="unreadCount === 0"
                                    :class="unreadCount === 0 ? 'text-gray-400 cursor-not-allowed' : 'text-blue-600 hover:text-blue-800'"
                                    class="text-sm">
                                    Đánh dấu tất cả đã đọc
                                </button>
                            </div>

                            <!-- Desktop Push Prompt Banner -->
                            <div x-show="desktopPermission === 'default'"
                                class="px-4 py-2.5 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-100 flex items-center justify-between">
                                <div class="flex items-center text-xs text-blue-900">
                                    <i class="fas fa-bell text-blue-600 mr-2 text-sm"></i>
                                    <span>Bật thông báo đẩy trên trình duyệt</span>
                                </div>
                                <button @click.stop="requestPushPermission()"
                                    class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-medium px-2.5 py-1 rounded shadow-sm transition-all hover:scale-105">
                                    Bật ngay
                                </button>
                            </div>

                            <!-- Notification List -->
                            <div class="max-h-96 overflow-y-auto">
                                <template x-if="notifications.length === 0">
                                    <div class="p-8 text-center text-gray-500">
                                        <i class="fas fa-bell-slash text-4xl mb-2"></i>
                                        <p>Không có thông báo</p>
                                    </div>
                                </template>

                                <template x-for="notification in notifications" :key="notification.id">
                                    <a :href="notification.link" @click="markAsRead(notification.id)"
                                        :class="!notification.is_read ? 'bg-blue-50' : ''"
                                        class="block p-4 border-b hover:bg-gray-50 transition-colors">
                                        <div class="flex items-start">
                                            <i :class="getIconClass(notification)" class="mt-1 mr-3 text-lg"></i>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-sm text-gray-800"
                                                    x-text="notification.title"></p>
                                                <p class="text-sm text-gray-600 mt-1" x-text="notification.message"></p>
                                                <p class="text-xs text-gray-400 mt-1"
                                                    x-text="formatTime(notification.created_at)"></p>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>

                            <!-- Footer -->
                            <div class="p-3 text-center border-t bg-gray-50">
                                <a href="{{ route('notifications.index') }}"
                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    Xem tất cả thông báo
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                            class="flex items-center space-x-1 sm:space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none">
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
                        <div x-show="open" x-cloak @click.away="open = false" x-transition
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200">
                            @auth
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ Auth::user()->position ?? Auth::user()->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                    <i class="fas fa-user-edit mr-2"></i>
                                    Chỉnh sửa hồ sơ
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center">
                                        <i class="fas fa-sign-out-alt mr-2"></i>
                                        Đăng xuất
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Đăng nhập
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-3 sm:p-4 lg:p-6 overflow-hidden min-h-0 flex flex-col">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-3 rounded-lg relative flex-shrink-0"
                        role="alert">
                        <span class="block sm:inline text-sm">{{ session('success') }}</span>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-3 sm:px-4 py-3 focus:outline-none"
                            onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-3 rounded-lg relative flex-shrink-0"
                        role="alert">
                        <span class="block sm:inline text-sm">{{ session('error') }}</span>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-3 sm:px-4 py-3 focus:outline-none"
                            onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 sm:px-4 py-3 rounded-lg relative flex-shrink-0"
                        role="alert">
                        <span class="block sm:inline text-sm">{!! session('warning') !!}</span>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-3 sm:px-4 py-3 focus:outline-none"
                            onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            {{-- <footer class="bg-white border-t border-gray-200 py-3 sm:py-4 px-4 sm:px-6 flex-shrink-0">
                <div class="text-center text-gray-500 text-xs sm:text-sm">
                    &copy; {{ date('Y') }} Mini ERP. Created by Ringnet.
                </div>
            </footer> --}}
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
            const toggleBtn = document.getElementById('toggleSidebar');
            const closeBtn = document.getElementById('closeSidebar');
            const loadingOverlay = document.getElementById('loadingOverlay');

            // Check saved state
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

            function toggleSidebar() {
                const isLargeScreen = window.innerWidth >= 1024;

                if (isLargeScreen) {
                    // Desktop: collapse to icon-only or expand
                    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
                    if (isCollapsed) {
                        sidebar.classList.remove('sidebar-collapsed', 'lg:w-16');
                        sidebar.classList.add('lg:w-64');
                        localStorage.setItem('sidebarCollapsed', 'false');
                    } else {
                        sidebar.classList.remove('lg:w-64');
                        sidebar.classList.add('sidebar-collapsed', 'lg:w-16');
                        localStorage.setItem('sidebarCollapsed', 'true');
                    }
                } else {
                    // Mobile: open sidebar
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }

            // Apply saved state on load
            if (sidebarCollapsed && window.innerWidth >= 1024) {
                sidebar.classList.remove('lg:w-64');
                sidebar.classList.add('sidebar-collapsed', 'lg:w-16');
            }

            if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1024) {
                    closeSidebar();
                    // Restore desktop state
                    const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    if (collapsed) {
                        sidebar.classList.remove('lg:w-64');
                        sidebar.classList.add('sidebar-collapsed', 'lg:w-16');
                    } else {
                        sidebar.classList.remove('sidebar-collapsed', 'lg:w-16');
                        sidebar.classList.add('lg:w-64');
                    }
                }
            });

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

            // Dropdown toggle functionality
            window.toggleDropdown = function (sectionId) {
                const dropdown = document.getElementById('dropdown-' + sectionId);
                const arrow = document.getElementById('arrow-' + sectionId);

                if (dropdown.classList.contains('collapsed')) {
                    // Open dropdown
                    dropdown.classList.remove('collapsed');
                    dropdown.style.maxHeight = dropdown.scrollHeight + 'px';
                    arrow.classList.add('rotated');
                    localStorage.setItem('dropdown-' + sectionId, 'open');
                } else {
                    // Close dropdown
                    dropdown.style.maxHeight = '0';
                    dropdown.classList.add('collapsed');
                    arrow.classList.remove('rotated');
                    localStorage.setItem('dropdown-' + sectionId, 'closed');
                }
            };

            // Initialize dropdown states from localStorage
            const sections = ['masterData', 'warehouse', 'reports', 'accounting', 'sales', 'purchasing', 'system', 'access', 'technical'];
            sections.forEach(function (sectionId) {
                const dropdown = document.getElementById('dropdown-' + sectionId);
                const arrow = document.getElementById('arrow-' + sectionId);

                if (dropdown && arrow) {
                    const savedState = localStorage.getItem('dropdown-' + sectionId);

                    if (savedState === 'closed') {
                        dropdown.style.maxHeight = '0';
                        dropdown.classList.add('collapsed');
                        arrow.classList.remove('rotated');
                    } else {
                        // Default to open
                        dropdown.style.maxHeight = dropdown.scrollHeight + 'px';
                        dropdown.classList.remove('collapsed');
                        arrow.classList.add('rotated');
                    }
                }
            });

            // Auto-open the section containing the active page
            sections.forEach(function (sectionId) {
                const dropdown = document.getElementById('dropdown-' + sectionId);
                if (dropdown) {
                    const activeLink = dropdown.querySelector('a.bg-primary');
                    if (activeLink) {
                        const arrow = document.getElementById('arrow-' + sectionId);
                        dropdown.classList.remove('collapsed');
                        dropdown.style.maxHeight = dropdown.scrollHeight + 'px';
                        arrow.classList.add('rotated');
                        localStorage.setItem('dropdown-' + sectionId, 'open');
                    }
                }
            });
        });
    </script>

    <!-- Notification Bell Script -->
    <script src="{{ asset('js/notification-bell.js') }}"></script>

    <!-- SweetAlert Helpers -->
    <script src="{{ asset('js/sweetalert-helpers.js') }}"></script>

    @if(session('success_swal'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: {!! json_encode(session('success_swal')) !!},
                        confirmButtonText: 'Đồng ý',
                        confirmButtonColor: '#3B82F6'
                    });
                }
            });
        </script>
    @endif

    @if(session('error_swal'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Thất bại',
                        text: {!! json_encode(session('error_swal')) !!},
                        confirmButtonText: 'Đồng ý',
                        confirmButtonColor: '#EF4444'
                    });
                }
            });
        </script>
    @endif

    <!-- Sidebar Badges Real-time Updater -->
    <script>
        function refreshSidebarBadges() {
            @if(auth()->check())
                fetch('{{ route("sidebar-badges") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(res => {
                        if (!res.ok) throw new Error('Network error');
                        return res.json();
                    })
                    .then(badges => {
                        document.querySelectorAll('[data-sidebar-badge]').forEach(badgeEl => {
                            const key = badgeEl.getAttribute('data-sidebar-badge');
                            const count = badges[key] || 0;
                            if (count > 0) {
                                badgeEl.textContent = count;
                                badgeEl.classList.remove('hidden');
                                badgeEl.classList.add('inline-flex');
                            } else {
                                badgeEl.textContent = '';
                                badgeEl.classList.add('hidden');
                                badgeEl.classList.remove('inline-flex');
                            }
                        });
                    })
                    .catch(() => { });
            @endif
        }

        // Poll every 60s when user is active
        setInterval(() => {
            if (!document.hidden) {
                refreshSidebarBadges();
            }
        }, 60000);

        // Allow other scripts to trigger badge update
        window.addEventListener('refreshSidebarBadges', refreshSidebarBadges);
    </script>

    @include('partials.file-preview-modal')
    @include('partials.excel-column-filter')
    @stack('modals')
    @stack('scripts')
</body>

</html>