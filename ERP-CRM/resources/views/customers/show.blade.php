@extends('layouts.app')

@section('title', 'Chi tiết khách hàng')
@section('page-title', 'Chi tiết khách hàng')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <a href="{{ route('customers.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <div class="flex gap-2">
            <a href="{{ route('customers.edit', $customer->id) }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-edit mr-2"></i>Chỉnh sửa
            </a>
            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-700 transition-colors delete-btn"
                        data-name="{{ $customer->name }}">
                    <i class="fas fa-trash mr-2"></i>Xóa
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content - 2 columns -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Thông tin cơ bản -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-user mr-2 text-primary"></i>Thông tin cơ bản
                    </h2>
                    @if($customer->type == 'vip')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-crown mr-1"></i>VIP
                        </span>
                    @else
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                            Thường
                        </span>
                    @endif
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Mã khách hàng</label>
                            <p class="text-base font-semibold text-gray-900">{{ $customer->code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Tên khách hàng</label>
                            <p class="text-base font-semibold text-gray-900">{{ $customer->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <p class="text-base text-gray-900">
                                <a href="mailto:{{ $customer->email }}" class="text-primary hover:underline">
                                    <i class="fas fa-envelope mr-1 text-gray-400"></i>{{ $customer->email }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Điện thoại</label>
                            <p class="text-base text-gray-900">
                                <a href="tel:{{ $customer->phone }}" class="text-primary hover:underline">
                                    <i class="fas fa-phone mr-1 text-gray-400"></i>{{ $customer->phone }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Người liên hệ</label>
                            <p class="text-base text-gray-900">
                                <i class="fas fa-user-circle mr-1 text-gray-400"></i>
                                {{ $customer->contact_person ?: 'Chưa cập nhật' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Mã số thuế</label>
                            <p class="text-base text-gray-900">{{ $customer->tax_code ?: 'Chưa cập nhật' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Địa chỉ</label>
                            <p class="text-base text-gray-900">
                                <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                                {{ $customer->address ?: 'Chưa cập nhật' }}
                            </p>
                        </div>
                        @if($customer->website)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Website</label>
                            <p class="text-base text-gray-900">
                                <a href="{{ $customer->website }}" target="_blank" class="text-primary hover:underline">
                                    <i class="fas fa-globe mr-1 text-gray-400"></i>{{ $customer->website }}
                                    <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                </a>
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Ghi chú -->
            @if($customer->note)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 whitespace-pre-line">{{ $customer->note }}</p>
                </div>
            </div>
            @endif

            <!-- Danh sách dự án -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-project-diagram mr-2 text-primary"></i>Dự án liên quan
                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $customer->projects->count() }}
                        </span>
                    </h2>
                </div>
                <div class="p-6">
                    @if($customer->projects->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-folder-open text-4xl mb-2"></i>
                            <p>Chưa có dự án nào</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($customer->projects as $project)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <a href="{{ route('projects.show', $project->id) }}" 
                                           class="text-lg font-semibold text-primary hover:underline">
                                            {{ $project->code }}
                                        </a>
                                        <p class="text-sm text-gray-600 mt-1">{{ $project->name }}</p>
                                    </div>
                                    @if($project->status === 'active')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Đang thực hiện
                                        </span>
                                    @elseif($project->status === 'completed')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Hoàn thành
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ ucfirst($project->status) }}
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Địa điểm:</span>
                                        <span class="text-gray-900 ml-1">{{ $project->location ?: 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Ngày bắt đầu:</span>
                                        <span class="text-gray-900 ml-1">{{ $project->start_date ? $project->start_date->format('d/m/Y') : 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Người quản lý:</span>
                                        <span class="text-gray-900 ml-1">{{ $project->manager->name ?? 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Đơn xuất kho:</span>
                                        <span class="text-gray-900 ml-1 font-semibold">{{ $project->exports->count() }}</span>
                                    </div>
                                </div>

                                @if($project->exports->isNotEmpty())
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500 mb-2">Đơn xuất kho gần nhất:</p>
                                    <div class="flex items-center justify-between text-sm">
                                        <a href="{{ route('exports.show', $project->exports->first()->id) }}" 
                                           class="text-primary hover:underline font-medium">
                                            {{ $project->exports->first()->code }}
                                        </a>
                                        <span class="text-gray-500">
                                            {{ $project->exports->first()->date->format('d/m/Y') }}
                                        </span>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Danh sách phiếu xuất kho -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-arrow-up mr-2 text-orange-500"></i>Phiếu xuất kho
                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                            {{ $customer->exports->count() }}
                        </span>
                    </h2>
                </div>
                <div class="p-6">
                    @if($customer->exports->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-box-open text-4xl mb-2"></i>
                            <p>Chưa có phiếu xuất nào</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($customer->exports->take(5) as $export)
                            <div class="border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between mb-2">
                                    <a href="{{ route('exports.show', $export->id) }}" 
                                       class="font-semibold text-orange-600 hover:underline">
                                        {{ $export->code }}
                                    </a>
                                    @if($export->status === 'pending')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Chờ xử lý
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Hoàn thành
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-600">
                                    <div class="flex justify-between">
                                        <span>Ngày xuất:</span>
                                        <span class="font-medium">{{ $export->date->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Số lượng:</span>
                                        <span class="font-medium">{{ number_format($export->total_qty) }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            
                            @if($customer->exports->count() > 5)
                            <div class="text-center pt-2">
                                <a href="{{ route('exports.index', ['customer_id' => $customer->id]) }}" 
                                   class="text-sm text-primary hover:underline">
                                    Xem tất cả {{ $customer->exports->count() }} phiếu xuất →
                                </a>
                            </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar - 1 column -->
        <div class="space-y-6">
            <!-- Công nợ -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-credit-card mr-2 text-primary"></i>Thông tin công nợ
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <label class="block text-sm font-medium text-blue-700 mb-1">Hạn mức nợ</label>
                        <p class="text-2xl font-bold text-blue-900">{{ number_format($customer->debt_limit) }} đ</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <label class="block text-sm font-medium text-green-700 mb-1">Số ngày nợ</label>
                        <p class="text-2xl font-bold text-green-900">{{ $customer->debt_days }} ngày</p>
                    </div>
                </div>
            </div>

            <!-- Thông tin hệ thống -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-info-circle mr-2 text-primary"></i>Thông tin hệ thống
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Ngày tạo</span>
                        <span class="text-sm font-medium text-gray-900">{{ $customer->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Cập nhật lần cuối</span>
                        <span class="text-sm font-medium text-gray-900">{{ $customer->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Thao tác nhanh</h3>
                <div class="space-y-2">
                    <a href="{{ route('customers.edit', $customer->id) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                    </a>
                    <a href="{{ route('customers.index') }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-list mr-2"></i>Danh sách
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
