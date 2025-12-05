@extends('layouts.app')

@section('title', 'Chi tiết báo cáo')
@section('page-title', 'Chi Tiết Báo Cáo: ' . $damagedGood->code)

@section('content')
<div class="space-y-4">
    @if(session('success'))
        <div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center">
        <a href="{{ route('damaged-goods.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
        </a>
        <div class="flex gap-2">
            <a href="{{ route('damaged-goods.edit', $damagedGood) }}" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-sm">
                <i class="fas fa-edit mr-1"></i> Sửa
            </a>
            @if($damagedGood->status !== 'processed')
                <form action="{{ route('damaged-goods.destroy', $damagedGood) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm">
                        <i class="fas fa-trash mr-1"></i> Xóa
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Main Info -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Thông Tin Chung</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Mã báo cáo</label>
                        <p class="text-gray-900 font-medium">{{ $damagedGood->code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Loại</label>
                        <p>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $damagedGood->type === 'damaged' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $damagedGood->getTypeLabel() }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Sản phẩm</label>
                        <p class="text-gray-900">{{ $damagedGood->product->name }} ({{ $damagedGood->product->code }})</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Số lượng</label>
                        <p class="text-gray-900">{{ number_format($damagedGood->quantity, 2) }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Ngày phát hiện</label>
                        <p class="text-gray-900">{{ $damagedGood->discovery_date->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Người phát hiện</label>
                        <p class="text-gray-900">{{ $damagedGood->discoveredBy->name }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-500">Lý do</label>
                    <p class="text-gray-900 mt-1">{{ $damagedGood->reason }}</p>
                </div>

                @if($damagedGood->solution)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-500">Giải pháp xử lý</label>
                        <p class="text-gray-900 mt-1">{{ $damagedGood->solution }}</p>
                    </div>
                @endif

                @if($damagedGood->note)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-500">Ghi chú</label>
                        <p class="text-gray-900 mt-1">{{ $damagedGood->note }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Side Info -->
        <div class="space-y-4">
            <!-- Value Card -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Giá Trị</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Giá trị gốc</label>
                        <p class="text-xl font-bold text-gray-900">{{ number_format($damagedGood->original_value, 0) }}đ</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Giá trị thu hồi</label>
                        <p class="text-xl font-bold text-green-600">{{ number_format($damagedGood->recovery_value, 0) }}đ</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Tổn thất</label>
                        <p class="text-xl font-bold text-red-600">{{ number_format($damagedGood->getLossAmount(), 0) }}đ</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Tỷ lệ thu hồi</label>
                        <p class="text-xl font-bold text-gray-900">{{ number_format($damagedGood->getRecoveryRate(), 1) }}%</p>
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Trạng Thái</h3>
                </div>
                <div class="p-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-500">Trạng thái hiện tại</label>
                        <p class="mt-1">
                            @if($damagedGood->status == 'pending')
                                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $damagedGood->getStatusLabel() }}</span>
                            @elseif($damagedGood->status == 'approved')
                                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">{{ $damagedGood->getStatusLabel() }}</span>
                            @elseif($damagedGood->status == 'rejected')
                                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">{{ $damagedGood->getStatusLabel() }}</span>
                            @else
                                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">{{ $damagedGood->getStatusLabel() }}</span>
                            @endif
                        </p>
                    </div>

                    @if($damagedGood->status !== 'processed')
                        <form action="{{ route('damaged-goods.update-status', $damagedGood) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            
                            <div class="mb-3">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Cập nhật trạng thái</label>
                                <select name="status" id="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" required>
                                    <option value="pending" {{ $damagedGood->status == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                                    <option value="approved" {{ $damagedGood->status == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                                    <option value="rejected" {{ $damagedGood->status == 'rejected' ? 'selected' : '' }}>Từ chối</option>
                                    <option value="processed" {{ $damagedGood->status == 'processed' ? 'selected' : '' }}>Đã xử lý</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="solution" class="block text-sm font-medium text-gray-700 mb-1">Giải pháp</label>
                                <textarea name="solution" id="solution" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ $damagedGood->solution }}</textarea>
                            </div>

                            <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                                <i class="fas fa-check mr-1"></i> Cập nhật
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
