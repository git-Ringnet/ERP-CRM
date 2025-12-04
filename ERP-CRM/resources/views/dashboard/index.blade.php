@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Summary Cards Row 1 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Customers Card -->
        <a href="/customers" class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between text-white">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">Khách hàng</p>
                    <p class="text-3xl font-bold">{{ $totalCustomers }}</p>
                    <p class="text-blue-100 text-xs mt-2">{{ $vipCustomers }} VIP ({{ $vipPercentage }}%)</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
        </a>

        <!-- Suppliers Card -->
        <a href="/suppliers" class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between text-white">
                <div>
                    <p class="text-green-100 text-sm font-medium mb-1">Nhà cung cấp</p>
                    <p class="text-3xl font-bold">{{ $totalSuppliers }}</p>
                    <p class="text-green-100 text-xs mt-2">Đối tác kinh doanh</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-truck text-2xl"></i>
                </div>
            </div>
        </a>

        <!-- Employees Card -->
        <a href="/employees" class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between text-white">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">Nhân viên</p>
                    <p class="text-3xl font-bold">{{ $totalEmployees }}</p>
                    <p class="text-purple-100 text-xs mt-2">{{ $activeEmployees }} đang làm việc</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-user-tie text-2xl"></i>
                </div>
            </div>
        </a>

        <!-- Products Card -->
        <a href="/products" class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between text-white">
                <div>
                    <p class="text-orange-100 text-sm font-medium mb-1">Sản phẩm</p>
                    <p class="text-3xl font-bold">{{ $totalProducts }}</p>
                    @if($lowStockProducts > 0)
                        <p class="text-orange-100 text-xs mt-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ $lowStockProducts }} sắp hết
                        </p>
                    @else
                        <p class="text-orange-100 text-xs mt-2">Tồn kho ổn định</p>
                    @endif
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-box text-2xl"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Customer Types Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Phân loại khách hàng</h2>
                <i class="fas fa-chart-pie text-blue-500"></i>
            </div>
            <div class="relative" style="height: 250px;">
                <canvas id="customerTypeChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="bg-blue-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-blue-600 font-medium">Thường</p>
                    <p class="text-xl font-bold text-blue-700">{{ $normalCustomers }}</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-yellow-600 font-medium">VIP</p>
                    <p class="text-xl font-bold text-yellow-700">{{ $vipCustomers }}</p>
                </div>
            </div>
        </div>

        <!-- Employee Departments Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Nhân viên theo phòng ban</h2>
                <i class="fas fa-chart-bar text-purple-500"></i>
            </div>
            <div class="relative" style="height: 250px;">
                <canvas id="departmentChart"></canvas>
            </div>
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-600">Tổng: <span class="font-bold text-purple-600">{{ $totalEmployees }}</span> nhân viên</p>
            </div>
        </div>

        <!-- Product Types Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Loại quản lý sản phẩm</h2>
                <i class="fas fa-chart-doughnut text-orange-500"></i>
            </div>
            <div class="relative" style="height: 250px;">
                <canvas id="productTypeChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2">
                <div class="bg-amber-50 rounded-lg p-2 text-center">
                    <p class="text-xs text-amber-600 font-medium">Thường</p>
                    <p class="text-lg font-bold text-amber-700">{{ $productsByType['normal'] ?? 0 }}</p>
                </div>
                <div class="bg-red-50 rounded-lg p-2 text-center">
                    <p class="text-xs text-red-600 font-medium">Serial</p>
                    <p class="text-lg font-bold text-red-700">{{ $productsByType['serial'] ?? 0 }}</p>
                </div>
                <div class="bg-cyan-50 rounded-lg p-2 text-center">
                    <p class="text-xs text-cyan-600 font-medium">Lô</p>
                    <p class="text-lg font-bold text-cyan-700">{{ $productsByType['lot'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-history mr-2 text-gray-400"></i>
                    Hoạt động gần đây
                </h2>
                <span class="text-sm text-gray-500">10 hoạt động mới nhất</span>
            </div>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Loại</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tên</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentActivities as $activity)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($activity['type'] === 'customer')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                        <i class="fas fa-users mr-1.5"></i>Khách hàng
                                    </span>
                                @elseif($activity['type'] === 'supplier')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fas fa-truck mr-1.5"></i>Nhà cung cấp
                                    </span>
                                @elseif($activity['type'] === 'employee')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                        <i class="fas fa-user-tie mr-1.5"></i>Nhân viên
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                        <i class="fas fa-box mr-1.5"></i>Sản phẩm
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $activity['name'] }}</p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-clock mr-1 text-gray-400"></i>
                                    {{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}
                                </p>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p class="text-sm">Chưa có hoạt động nào</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Customer Types Chart
    const customerTypeCtx = document.getElementById('customerTypeChart');
    if (customerTypeCtx) {
        const customerTypeData = @json($customersByType);
        new Chart(customerTypeCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Thường', 'VIP'],
                datasets: [{
                    data: [customerTypeData.normal || 0, customerTypeData.vip || 0],
                    backgroundColor: ['#3B82F6', '#F59E0B'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Employee Departments Chart
    const departmentCtx = document.getElementById('departmentChart');
    if (departmentCtx) {
        const departmentData = @json($employeesByDepartment);
        const labels = Object.keys(departmentData);
        const data = Object.values(departmentData);
        
        new Chart(departmentCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Số nhân viên',
                    data: data,
                    backgroundColor: '#8B5CF6',
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 13
                        },
                        bodyFont: {
                            size: 12
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Product Types Chart
    const productTypeCtx = document.getElementById('productTypeChart');
    if (productTypeCtx) {
        const productTypeData = @json($productsByType);
        new Chart(productTypeCtx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Thường', 'Serial', 'Lô'],
                datasets: [{
                    data: [
                        productTypeData.normal || 0,
                        productTypeData.serial || 0,
                        productTypeData.lot || 0
                    ],
                    backgroundColor: ['#F59E0B', '#EF4444', '#06B6D4'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
