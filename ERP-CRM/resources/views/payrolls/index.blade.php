@extends('layouts.app')

@section('title', 'Quản lý Bảng lương')
@section('page-title', 'Danh sách Bảng lương')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Tất cả bảng lương</h2>
        <a href="{{ route('payrolls.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm whitespace-nowrap">
            <i class="fas fa-plus mr-2"></i>Tạo Bảng lương mới
        </a>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tiêu đề</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Kỳ tính lương</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Ngày chuẩn</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Ngày tạo</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($payrolls as $payroll)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        <a href="{{ route('payrolls.show', $payroll->id) }}" class="text-primary hover:underline">
                            {{ $payroll->title }}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-center font-medium text-gray-700">
                        Tháng {{ str_pad($payroll->month, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->year }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($payroll->status == 'draft')
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">Bản nháp</span>
                        @elseif($payroll->status == 'pending_approval')
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Chờ duyệt</span>
                        @elseif($payroll->status == 'approved')
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">Đã duyệt</span>
                        @elseif($payroll->status == 'paid')
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Đã thanh toán</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-600">
                        {{ $payroll->standard_working_days }}
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($payroll->created_at)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('payrolls.show', $payroll->id) }}" class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($payroll->status != 'paid')
                            <form action="{{ route('payrolls.destroy', $payroll->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors delete-btn" data-name="{{ $payroll->title }}" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-money-check-alt text-5xl mb-4 text-gray-300"></i>
                        <p class="text-base font-medium">Chưa có bảng lương nào được tạo.</p>
                        <a href="{{ route('payrolls.create') }}" class="text-primary hover:underline mt-2 inline-block">Tạo Bảng lương đầu tiên</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($payrolls->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $payrolls->links() }}
    </div>
    @endif
</div>
@endsection
