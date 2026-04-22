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
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap"
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
                <a href="{{ route('dashboard') }}"
                    class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-primary text-white' : '' }}">
                    <i class="fas fa-tachometer-alt w-6 flex-shrink-0"></i>
                    <span class="ml-3 sidebar-text whitespace-nowrap">Dashboard</span>
                </a>

                {{-- @can('view_business_dashboard')
                <a href="{{ route('dashboard.business-activity') }}"
                    class="flex items-center px-4 py-3 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('dashboard.business-activity*') ? 'bg-primary text-white' : '' }}">
                    <i class="fas fa-chart-line w-6 text-blue-400 flex-shrink-0"></i>
                    <span class="ml-3 sidebar-text whitespace-nowrap">Dashboard Kinh Doanh</span>
                </a>
                @endcan --}}

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
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                            onclick="toggleDropdown('masterData')">
                            <div class="flex items-center">
                                <i class="fas fa-database w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Master Data</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-masterData"></i>
                        </div>

                        <div class="dropdown-section" id="dropdown-masterData">
                            @can('view_customers')
                                <a href="{{ route('customers.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('customers.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-users w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Khách hàng</span>
                                </a>
                            @endcan

                            @can('view_suppliers')
                                <a href="{{ route('suppliers.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-truck w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Nhà cung cấp</span>
                                </a>
                            @endcan

                            @can('view_employees')
                                <a href="{{ route('employees.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('employees.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-user-tie w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Nhân viên</span>
                                </a>
                            @endcan

                            @can('view_products')
                                <a href="{{ route('products.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('products.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-box w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Sản phẩm</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @canany(['view_warehouses', 'view_inventory', 'view_imports', 'view_exports', 'view_transfers', 'view_damaged_goods'])
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                            onclick="toggleDropdown('warehouse')">
                            <div class="flex items-center">
                                <i class="fas fa-warehouse w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Kho hàng</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-warehouse"></i>
                        </div>

                        <div class="dropdown-section" id="dropdown-warehouse">
                            @can('view_warehouses')
                                <a href="{{ route('warehouses.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('warehouses.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-warehouse w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Quản lý kho</span>
                                </a>
                            @endcan

                            @can('view_inventory')
                                <a href="{{ route('inventory.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('inventory.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-boxes w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Tồn kho</span>
                                </a>
                            @endcan

                            @can('view_imports')
                                <a href="{{ route('imports.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('imports.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-arrow-down w-6 text-blue-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Nhập kho</span>
                                </a>
                            @endcan

                            @can('view_exports')
                                <a href="{{ route('exports.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('exports.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-arrow-up w-6 text-orange-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Xuất kho</span>
                                </a>
                            @endcan

                            @can('view_transfers')
                                <a href="{{ route('transfers.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('transfers.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-exchange-alt w-6 text-purple-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Chuyển kho</span>
                                </a>
                            @endcan

                            @can('view_damaged_goods')
                                <a href="{{ route('damaged-goods.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('damaged-goods.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-exclamation-triangle w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Hàng hư hỏng</span>
                                </a>
                            @endcan
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

                @canany(['view_leads', 'view_opportunities', 'view_activities', 'view_customer_care_stages', 'view_quotations', 'view_sales', 'view_projects', 'view_customer_debts', 'view_cost_formulas', 'view_sale_reports', 'view_marketing_events'])
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                            onclick="toggleDropdown('sales')">
                            <div class="flex items-center">
                                <i class="fas fa-shopping-cart w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Bán hàng</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-sales"></i>
                        </div>

                        <div class="dropdown-section" id="dropdown-sales">
                            @can('view_leads')
                                <a href="{{ route('leads.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('leads.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-bullseye w-6 text-cyan-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Đấu mối</span>
                                </a>
                            @endcan

                            @can('view_opportunities')
                                <a href="{{ route('opportunities.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('opportunities.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-funnel-dollar w-6 text-yellow-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Cơ hội</span>
                                </a>
                            @endcan

                            {{-- @can('view_activities')
                            <a href="{{ route('activities.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('activities.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-tasks w-6 text-green-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Công việc</span>
                            </a>
                            @endcan

                            @can('view_customer_care_stages')
                            <a href="{{ route('customer-care-stages.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('customer-care-stages.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-heart w-6 text-pink-400 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Chăm sóc KH</span>
                            </a>
                            @endcan --}}

                            @can('view_marketing_events')
                                <a href="{{ route('marketing-events.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('marketing-events.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-calendar-alt w-6 text-purple-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Marketing Events</span>
                                </a>
                            @endcan

                            @can('view_quotations')
                                <a href="{{ route('quotations.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('quotations.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-file-alt w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Báo giá</span>
                                </a>
                            @endcan

                            @can('view_sales')
                                <a href="{{ route('sales.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('sales.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-shopping-cart w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Đơn hàng bán</span>
                                </a>
                            @endcan

                            @can('view_projects')
                                <a href="{{ route('projects.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('projects.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-project-diagram w-6 text-purple-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Quản lý dự án</span>
                                </a>
                            @endcan

                            @can('view_customer_debts')
                                <a href="{{ route('customer-debts.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('customer-debts.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-file-invoice-dollar w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Công nợ khách hàng</span>
                                </a>
                            @endcan

                            @can('view_cost_formulas')
                                <a href="{{ route('cost-formulas.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('cost-formulas.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-calculator w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Công thức chi phí</span>
                                </a>
                            @endcan

                            @can('view_sale_reports')
                                <a href="{{ route('sale-reports.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('sale-reports.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-chart-line w-6 text-pink-400 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Báo cáo bán hàng</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @canany(['view_supplier_price_lists', 'view_purchase_requests', 'view_all_purchase_requests', 'view_supplier_quotations', 'view_purchase_orders', 'view_all_purchase_orders', 'view_shipping_allocations', 'view_purchase_reports'])
                    <div class="mt-4">
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                            onclick="toggleDropdown('purchasing')">
                            <div class="flex items-center">
                                <i class="fas fa-file-contract w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Mua hàng</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-purchasing"></i>
                        </div>

                        <div class="dropdown-section" id="dropdown-purchasing">
                            @can('view_supplier_price_lists')
                                <a href="{{ route('supplier-price-lists.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('supplier-price-lists.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-tags w-6 text-green-400"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Bảng giá</span>
                                </a>
                            @endcan

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

                            @can('view_purchase_orders')
                                <a href="{{ route('purchase-orders.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('purchase-orders.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-file-contract w-6 text-blue-400"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Đặt hàng với hãng (PO)</span>
                                </a>
                            @endcan

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

                            <a href="{{ route('supplier-debts.index') }}"
                                class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('supplier-debts.*') ? 'bg-primary text-white' : '' }}">
                                <i class="fas fa-file-invoice-dollar w-6 text-emerald-400"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap">Công nợ NCC</span>
                            </a>
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
                        <div class="section-header flex items-center justify-between px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-colors"
                            onclick="toggleDropdown('system')">
                            <div class="flex items-center">
                                <i class="fas fa-cog w-6 flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text whitespace-nowrap font-semibold">Hệ thống</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow sidebar-text" id="arrow-system"></i>
                        </div>

                        <div class="dropdown-section" id="dropdown-system">
                            @can('view_approval_workflows')
                                <a href="{{ route('approval-workflows.index') }}"
                                    class="flex items-center px-4 py-2 ml-4 text-gray-300 hover:bg-primary hover:text-white rounded-lg transition-colors {{ request()->routeIs('approval-workflows.*') ? 'bg-primary text-white' : '' }}">
                                    <i class="fas fa-project-diagram w-6 flex-shrink-0"></i>
                                    <span class="ml-3 sidebar-text whitespace-nowrap">Quy trình duyệt</span>
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
            </nav>
        </aside>


        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen min-w-0">
            <!-- Top Header -->
            <header
                class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
                <div class="flex items-center min-w-0 flex-1">
                    <h1 class="text-base sm:text-lg font-semibold text-gray-800 truncate">
                        @yield('page-title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- Quick Attendance Link -->
                    @if(false)
                        <a href="{{ route('attendance.index') }}"
                            class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-green-600 hover:bg-green-700 text-white text-xs sm:text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            title="Chấm công GPS">
                            <i class="fas fa-map-marker-alt mr-1.5 sm:mr-2"></i>
                            <span class="whitespace-nowrap">Chấm công</span>
                        </a>
                    @endif

                    <!-- Notification Bell -->
                    <div class="relative" x-data="notificationBell()" x-init="init()">
                        <!-- Bell Icon with Badge -->
                        <button @click="toggleDropdown()"
                            class="relative text-gray-600 hover:text-gray-900 focus:outline-none">
                            <i class="fas fa-bell text-lg sm:text-xl"></i>
                            <span x-show="unreadCount > 0" x-text="unreadCount > 99 ? '99+' : unreadCount"
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-semibold">
                            </span>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="isOpen" x-cloak @click.away="isOpen = false" x-transition
                            class="absolute right-0 mt-2 w-96 bg-white shadow-lg rounded-lg z-50 border border-gray-200">
                            <!-- Header -->
                            <div class="flex justify-between items-center p-4 border-b">
                                <h3 class="font-semibold text-gray-800">Thông báo</h3>
                                <button @click="markAllAsRead()" :disabled="unreadCount === 0"
                                    :class="unreadCount === 0 ? 'text-gray-400 cursor-not-allowed' : 'text-blue-600 hover:text-blue-800'"
                                    class="text-sm">
                                    Đánh dấu tất cả đã đọc
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
            const sections = ['masterData', 'warehouse', 'reports', 'accounting', 'sales', 'purchasing', 'system', 'access'];
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

    @stack('scripts')
</body>

</html>