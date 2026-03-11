@extends('layouts.app')

@section('title', 'Thêm mới Tiêu chí KPI')
@section('page-title', 'Thêm mới Tiêu chí KPI')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex items-center gap-3">
            <a href="{{ route('department-kpi-criteria.index') }}" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="font-semibold text-gray-800">Thêm Tiêu chí Đánh giá</h2>
        </div>

        <form action="{{ route('department-kpi-criteria.store') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên tiêu chí <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="VD: Doanh số kinh doanh, Điểm hài lòng khách hàng..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bộ phận áp dụng <span class="text-red-500">*</span></label>
                    <select name="department" id="department_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('department') border-red-500 @enderror">
                        <option value="">-- Chọn bộ phận --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}" {{ old('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                        <option value="other">Bộ phận khác (Sẽ gõ tay)</option>
                    </select>
                    <input type="text" name="department_manual" id="department_manual" placeholder="Nhập tên bộ phận..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mt-2 hidden" disabled>
                    @error('department') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trọng số (%) <span class="text-red-500">*</span></label>
                    <input type="number" name="weight" value="{{ old('weight', 0) }}" min="0" max="100" step="0.01" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('weight') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-1">Trọng số phần trăm của tiêu chí trong đợt đánh giá.</p>
                    @error('weight') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mục tiêu (Target)</label>
                    <input type="text" name="target" value="{{ old('target') }}" placeholder="VD: Đạt 1 tỷ, Lỗi < 5%..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('target') border-red-500 @enderror">
                    @error('target') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả / Ghi chú chi tiết</label>
                    <textarea name="description" rows="3" placeholder="Mô tả cách thức đánh giá tiêu chí này..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium">
                    <i class="fas fa-save mr-2"></i>Lưu Tiêu chí
                </button>
                <a href="{{ route('department-kpi-criteria.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                    Huỷ bỏ
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('department_select');
    const deptManual = document.getElementById('department_manual');

    deptSelect.addEventListener('change', function() {
        if (this.value === 'other') {
            deptManual.classList.remove('hidden');
            deptManual.disabled = false;
            deptSelect.name = 'department_ignore';
            deptManual.name = 'department';
        } else {
            deptManual.classList.add('hidden');
            deptManual.disabled = true;
            deptSelect.name = 'department';
            deptManual.name = 'department_manual';
        }
    });

    // Handle session validation errors if user was creating custom department
    if (deptSelect.value === 'other') {
        deptSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection
