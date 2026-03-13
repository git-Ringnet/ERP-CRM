@extends('layouts.app')

@section('title', 'Cấu hình Lương & Phụ cấp - ' . $employee->name)
@section('page-title', 'Cấu hình Lương & Phụ cấp')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('employees.show', $employee->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại hồ sơ
        </a>
    </div>

    <form action="{{ route('employees.salary-setup.update', $employee->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Lương cơ bản -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-money-bill-wave mr-2 text-primary"></i>Mức lương cơ bản (VNĐ)
                </h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-500 mb-3">Lương cố định được dùng làm cơ sở tính tỷ lệ ngày công, phần trăm phụ cấp (nếu có).</p>
                <div class="max-w-md">
                    <input type="number" name="salary" id="salary" value="{{ old('salary', $employee->salary) }}" min="0" step="1000"
                           class="w-full px-4 py-2 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary font-semibold text-green-700">
                </div>
            </div>
        </div>

        <!-- Phụ cấp & Khấu trừ -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list-ul mr-2 text-primary"></i>Danh sách Phụ cấp / Khấu trừ áp dụng
                </h2>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Kích hoạt</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Tên khoản</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Mức tiền / Tỷ lệ định lượng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($availableComponents as $comp)
                            @php
                                $isApplied = isset($employeeComponents[$comp->id]);
                                $currentAmount = $isApplied ? $employeeComponents[$comp->id]->amount : $comp->default_amount;
                            @endphp
                            <tr class="hover:bg-gray-50 {{ $isApplied ? 'bg-blue-50/30' : '' }}">
                                <td class="px-6 py-4 text-center w-20">
                                    <input type="checkbox" class="component-checkbox h-5 w-5 text-primary focus:ring-primary border-gray-300 rounded"
                                           id="checkbox_{{ $comp->id }}"
                                           {{ $isApplied ? 'checked' : '' }}>
                                </td>
                                <td class="px-6 py-4">
                                    <label for="checkbox_{{ $comp->id }}" class="font-medium text-gray-900 cursor-pointer block">
                                        {{ $comp->name }}
                                        @if($comp->is_taxable)
                                            <span class="inline-flex ml-2 items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-800" title="Tính thuế TNCN">Thuế</span>
                                        @endif
                                    </label>
                                </td>
                                <td class="px-6 py-4">
                                    @if($comp->type == 'allowance')
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Phụ cấp</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Khấu trừ</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="relative max-w-[200px]">
                                        <input type="number" step="any" min="0" 
                                               name="components[{{ $comp->id }}]" 
                                               id="input_{{ $comp->id }}" 
                                               value="{{ old('components.'.$comp->id, $currentAmount) }}"
                                               {{ !$isApplied ? 'disabled' : '' }}
                                               class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary disabled:bg-gray-100 disabled:text-gray-400">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-sm {{ $isApplied ? 'text-gray-700' : 'text-gray-400' }}" id="suffix_{{ $comp->id }}">
                                                {{ $comp->amount_type == 'percentage' ? '%' : 'đ' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        
                        @if($availableComponents->isEmpty())
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-exclamation-circle text-4xl mb-3 text-gray-300"></i>
                                <p>Chưa có danh mục Phụ cấp / Khấu trừ nào được định nghĩa trên hệ thống.</p>
                                <a href="{{ route('salary-components.index') }}" class="text-primary hover:underline mt-2 inline-block">Đến trang Cài đặt Danh mục</a>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            <div class="p-6 bg-gray-50 border-t border-gray-200">
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                    <i class="fas fa-save mr-2"></i>Lưu cấu hình
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.component-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const id = this.id.split('_')[1];
                const input = document.getElementById('input_' + id);
                const suffix = document.getElementById('suffix_' + id);
                const row = this.closest('tr');
                
                if (this.checked) {
                    input.disabled = false;
                    suffix.classList.remove('text-gray-400');
                    suffix.classList.add('text-gray-700');
                    row.classList.add('bg-blue-50/30');
                } else {
                    input.disabled = true;
                    suffix.classList.remove('text-gray-700');
                    suffix.classList.add('text-gray-400');
                    row.classList.remove('bg-blue-50/30');
                }
            });
        });
    });
</script>
@endpush
@endsection
