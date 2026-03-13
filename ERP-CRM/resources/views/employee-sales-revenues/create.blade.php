@extends('layouts.app')

@section('title', 'Ghi nhận Doanh số')
@section('page-title', 'Ghi nhận doanh số nhân viên kinh doanh')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Thông tin doanh số ghi nhận</h3>
            <p class="mt-1 text-sm text-gray-500">Chọn nhân viên và thời gian để lấy dữ liệu gợi ý từ hệ thống.</p>
        </div>

        <form action="{{ route('employee-sales-revenues.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên <span class="text-red-500">*</span></label>
                    <select name="user_id" id="user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <option value="">-- Chọn nhân viên --</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('user_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tháng <span class="text-red-500">*</span></label>
                    <select name="month" id="month" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (old('month', $currentMonth) == $m) ? 'selected' : '' }}>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Năm <span class="text-red-500">*</span></label>
                    <select name="year" id="year" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        @for($y = date('Y'); $y >= 2023; $y--)
                            <option value="{{ $y }}" {{ (old('year', $currentYear) == $y) ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="flex justify-center">
                <button type="button" id="btn-suggest" class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                    <i class="fas fa-magic mr-2"></i> Lấy dữ liệu gợi ý từ hệ thống
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-100">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tổng doanh thu <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="total_revenue" id="total_revenue" value="{{ old('total_revenue', 0) }}" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary pr-10">
                        <span class="absolute right-3 top-2 text-gray-400">đ</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tổng lợi nhuận <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="total_profit" id="total_profit" value="{{ old('total_profit', 0) }}" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary pr-10">
                        <span class="absolute right-3 top-2 text-gray-400">đ</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng đơn hàng</label>
                    <input type="number" name="quantity_on_target" id="quantity_on_target" value="{{ old('quantity_on_target', 0) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">{{ old('note') }}</textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <a href="{{ route('employee-sales-revenues.index') }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition-colors">Hủy</a>
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors shadow-sm">
                    <i class="fas fa-save mr-2"></i> Lưu ghi nhận
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#btn-suggest').on('click', function() {
        const userId = $('#user_id').val();
        const month = $('#month').val();
        const year = $('#year').val();

        if (!userId) {
            alert('Vui lòng chọn nhân viên trước!');
            return;
        }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Đang lấy dữ liệu...');

        $.ajax({
            url: "{{ route('employee-sales-revenues.suggested') }}",
            type: "GET",
            data: {
                user_id: userId,
                month: month,
                year: year
            },
            success: function(response) {
                $('#total_revenue').val(response.revenue);
                $('#total_profit').val(response.profit);
                $('#quantity_on_target').val(response.count);
                
                if (response.count === 0) {
                    alert('Hệ thống không tìm thấy đơn hàng nào cho nhân viên này trong khoảng thời gian đã chọn.');
                }
            },
            error: function(xhr) {
                alert('Có lỗi xảy ra khi lấy dữ liệu gợi ý.');
                console.error(xhr);
            },
            complete: function() {
                $('#btn-suggest').prop('disabled', false).html('<i class="fas fa-magic mr-2"></i> Lấy dữ liệu gợi ý từ hệ thống');
            }
        });
    });
});
</script>
@endpush
@endsection
