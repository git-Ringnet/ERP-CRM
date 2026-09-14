@extends('layouts.app')

@section('title', 'Quản lý Mẫu Điều khoản Thanh toán')
@section('page-title', 'Quản lý Mẫu Điều khoản Thanh toán')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('settings.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại Cài đặt
        </a>
        <a href="{{ route('settings.payment-templates.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium text-sm rounded-lg hover:bg-primary-dark transition-all shadow-sm">
            <i class="fas fa-plus mr-2"></i> Thêm Mẫu Mới
        </a>
    </div>

    @if (session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-sm text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    @if (session('error'))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm text-sm text-red-700">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-amber-200/80 p-5 overflow-hidden">
        <form action="{{ route('settings.payment-templates.update-threshold') }}" method="POST" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            @csrf
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-sm">
                        <i class="fas fa-shield-alt"></i>
                    </span>
                    <h3 class="text-sm font-bold text-gray-900">Quy tắc Đơn hàng lớn — Bắt buộc thanh toán trước</h3>
                </div>
                <p class="text-xs text-gray-500 pl-10">
                    Đơn hàng bán có tổng giá trị từ hạn mức này trở lên sẽ <strong>không được phép</strong> cấu hình thanh toán 100% sau khi giao hàng (bắt buộc có ít nhất 1 đợt cọc/thanh toán trước khi đặt hàng hoặc xuất hàng).
                </p>
            </div>
            
            <div class="flex items-center gap-3 pl-10 md:pl-0">
                <div class="relative min-w-[200px] max-w-[240px]">
                    <input type="text" 
                           inputmode="numeric"
                           name="large_order_post_delivery_threshold" 
                           value="{{ number_format($largeOrderThreshold, 0, ',', '.') }}"
                           oninput="let v = this.value.replace(/\D/g, ''); this.value = v ? parseInt(v, 10).toLocaleString('vi-VN') : '0';"
                           class="w-full border border-gray-300 rounded-lg pl-3 pr-12 py-2 text-sm font-bold text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-amber-50/30 text-right">
                    <span class="absolute right-3 top-2.5 text-xs font-semibold text-gray-500">VNĐ</span>
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-lg transition-colors shadow-xs whitespace-nowrap flex items-center gap-1.5">
                    <i class="fas fa-save"></i>
                    <span>Lưu hạn mức</span>
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50/50">
            <h3 class="text-base font-bold text-gray-900">Danh sách Mẫu Điều khoản</h3>
            <p class="text-xs text-gray-500 mt-1">Cấu hình lộ trình thanh toán, các đợt cọc, và mốc chặn nghiệp vụ của hệ thống.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-xs border-b border-gray-200">
                        <th class="p-4">Tên mẫu</th>
                        <th class="p-4">Mô tả</th>
                        <th class="p-4 text-center">Số đợt</th>
                        <th class="p-4 text-center">Phiên bản</th>
                        <th class="p-4 text-center">Trạng thái</th>
                        <th class="p-4 text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($templates as $template)
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="p-4 font-semibold text-gray-800">{{ $template->name }}</td>
                        <td class="p-4 text-gray-500 max-w-xs truncate">{{ $template->description ?: '-' }}</td>
                        <td class="p-4 text-center font-bold text-gray-700">{{ $template->items_count }}</td>
                        <td class="p-4 text-center">
                            <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded-full">v{{ $template->version }}</span>
                        </td>
                        <td class="p-4 text-center">
                            <form action="{{ route('settings.payment-templates.toggle', $template->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center">
                                    @if($template->is_active)
                                    <span class="px-2.5 py-1 bg-green-50 text-green-700 text-xs font-bold rounded-full border border-green-200 cursor-pointer hover:bg-green-100">Hoạt động</span>
                                    @else
                                    <span class="px-2.5 py-1 bg-gray-50 text-gray-400 text-xs font-bold rounded-full border border-gray-200 cursor-pointer hover:bg-gray-100">Ngừng dùng</span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('settings.payment-templates.edit', $template->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('settings.payment-templates.destroy', $template->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mẫu điều khoản này?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Xóa">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-400 italic">Chưa có mẫu điều khoản thanh toán nào được cấu hình.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
