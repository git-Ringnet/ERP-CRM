@extends('layouts.app')

@section('title', 'Chi tiết kho')
@section('page-title', 'Chi tiết kho hàng')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">{{ $warehouse->name }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('warehouses.edit', $warehouse->id) }}" 
                   class="px-3 py-1.5 text-sm text-yellow-700 bg-yellow-100 rounded-lg hover:bg-yellow-200 transition-colors">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
                <a href="{{ route('warehouses.index') }}" 
                   class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i>Quay lại
                </a>
            </div>
        </div>
        
        <div class="p-4">
            <!-- Status Badge -->
            <div class="mb-4">
                @if($warehouse->status == 'active')
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>{{ $warehouse->status_label }}
                    </span>
                @elseif($warehouse->status == 'maintenance')
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        <i class="fas fa-tools mr-1"></i>{{ $warehouse->status_label }}
                    </span>
                @else
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>{{ $warehouse->status_label }}
                    </span>
                @endif
                <span class="ml-2 px-3 py-1 text-sm font-semibold rounded-full {{ $warehouse->type == 'physical' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                    <i class="fas fa-warehouse mr-1"></i>{{ $warehouse->type_label }}
                </span>
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500">Mã kho</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->code }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Tên kho</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Người quản lý</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->manager?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Số điện thoại</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->phone ?? '-' }}</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500">Diện tích</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->area ? number_format($warehouse->area, 2) . ' m²' : '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Sức chứa</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->capacity ? number_format($warehouse->capacity) : '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Loại sản phẩm</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->product_type ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Ngày tạo</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="mt-4">
                <label class="text-sm text-gray-500">Địa chỉ</label>
                <p class="font-medium text-gray-900">{{ $warehouse->address ?? '-' }}</p>
            </div>

            <!-- Features -->
            <div class="mt-4">
                <label class="text-sm text-gray-500 block mb-2">Tính năng</label>
                <div class="flex flex-wrap gap-2">
                    @if($warehouse->has_temperature_control)
                        <span class="px-3 py-1 text-sm rounded-full bg-cyan-100 text-cyan-800">
                            <i class="fas fa-thermometer-half mr-1"></i>Kiểm soát nhiệt độ
                        </span>
                    @endif
                    @if($warehouse->has_security_system)
                        <span class="px-3 py-1 text-sm rounded-full bg-indigo-100 text-indigo-800">
                            <i class="fas fa-shield-alt mr-1"></i>Hệ thống an ninh
                        </span>
                    @endif
                    @if(!$warehouse->has_temperature_control && !$warehouse->has_security_system)
                        <span class="text-gray-500">Không có tính năng đặc biệt</span>
                    @endif
                </div>
            </div>

            <!-- Note -->
            @if($warehouse->note)
            <div class="mt-4">
                <label class="text-sm text-gray-500">Ghi chú</label>
                <p class="font-medium text-gray-900 whitespace-pre-line">{{ $warehouse->note }}</p>
            </div>
            @endif
        </div>
    </div>
@endsection
