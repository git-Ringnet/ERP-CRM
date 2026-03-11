@extends('layouts.app')

@section('title', 'Tạo Kỳ Đánh giá KPI')
@section('page-title', 'Tạo Kỳ Đánh giá KPI Mới')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-sm">
        <form action="{{ route('department-kpis.store') }}" method="POST" class="p-6 space-y-6" id="kpi-form">
            @csrf

            <!-- Section: General Info -->
            <div>
                <h3 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Thông tin chung</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tựa đề KPI <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="VD: Đánh giá KPI Tháng 03/2026..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bộ phận <span class="text-red-500">*</span></label>
                        <select name="department" id="department_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                            <option value="">-- Chọn bộ phận --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ old('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                            <option value="other">Bộ phận khác (Sẽ gõ tay)</option>
                        </select>
                        <input type="text" name="department_manual" id="department_manual" placeholder="Nhập tên bộ phận..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mt-2 hidden" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kỳ đánh giá (Tháng/Năm) <span class="text-red-500">*</span></label>
                        <input type="month" name="evaluation_period" value="{{ old('evaluation_period', date('Y-m')) }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái khởi tạo</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                            <option value="draft">Bản nháp (Draft)</option>
                            <option value="pending">Chờ tự đánh giá (Pending)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section: Criteria -->
            <div>
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-lg font-medium text-gray-800">Chi tiết Tiêu chí đánh giá</h3>
                    <div class="space-x-2">
                        <button type="button" id="btn_load_criteria" class="px-3 py-1.5 bg-blue-100 text-blue-700 text-sm font-medium rounded hover:bg-blue-200 transition-colors hidden">
                            <i class="fas fa-sync-alt mr-1"></i> Load bộ tiêu chí chuẩn
                        </button>
                        <button type="button" id="btn_add_criterion" class="px-3 py-1.5 bg-green-100 text-green-700 text-sm font-medium rounded hover:bg-green-200 transition-colors">
                            <i class="fas fa-plus mr-1"></i> Thêm hàng tự do
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" id="criteria_table">
                        <thead class="bg-gray-50 text-gray-700 font-medium">
                            <tr>
                                <th class="px-3 py-2 w-1/3">Tên tiêu chí <span class="text-red-500">*</span></th>
                                <th class="px-3 py-2 w-1/4">Mục tiêu (Target)</th>
                                <th class="px-3 py-2 w-32">Trọng số (%) <span class="text-red-500">*</span></th>
                                <th class="px-3 py-2 w-16 text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="criteria_body" class="divide-y divide-gray-200">
                            <!-- Rows dynamic -->
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-3 py-3 text-right font-medium text-gray-700">Tổng Trọng số:</td>
                                <td class="px-3 py-3 font-bold text-gray-800" id="total_weight">0%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Note -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú chung</label>
                <textarea name="note" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary placeholder-gray-400" placeholder="Thông điệp cho bộ phận (Tùy chọn)...">{{ old('note') }}</textarea>
            </div>

            <div class="pt-4 border-t border-gray-100 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium transition-colors">
                    <i class="fas fa-save mr-2"></i>Lưu và Tạo Kỳ KPI
                </button>
                <a href="{{ route('department-kpis.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 0;
    const tbody = document.getElementById('criteria_body');
    const btnAdd = document.getElementById('btn_add_criterion');
    const btnLoad = document.getElementById('btn_load_criteria');
    const deptSelect = document.getElementById('department_select');
    const deptManual = document.getElementById('department_manual');

    // Handle department select
    deptSelect.addEventListener('change', function() {
        if (this.value === 'other') {
            deptManual.classList.remove('hidden');
            deptManual.disabled = false;
            deptSelect.name = 'department_ignore';
            deptManual.name = 'department';
            btnLoad.classList.add('hidden');
        } else {
            deptManual.classList.add('hidden');
            deptManual.disabled = true;
            deptSelect.name = 'department';
            deptManual.name = 'department_manual';
            
            if (this.value) {
                btnLoad.classList.remove('hidden');
            } else {
                btnLoad.classList.add('hidden');
            }
        }
    });

    if(deptSelect.value && deptSelect.value !== 'other') {
        btnLoad.classList.remove('hidden');
    }

    // Load defaults
    btnLoad.addEventListener('click', function() {
        const dept = deptSelect.value;
        if (!dept) return;

        fetch(`{{ url('api/department-kpi-criteria') }}?department=${encodeURIComponent(dept)}`)
            .then(res => res.json())
            .then(data => {
                if(data.length === 0) {
                    alert('Không tìm thấy Bộ tiêu chí chuẩn nào cho bộ phận này.');
                    return;
                }
                
                // Confirm clear?
                if(tbody.children.length > 0 && !confirm('Bạn có muốn ghi đè lên các tiêu chí hiện tại không?')) {
                    return;
                }
                
                tbody.innerHTML = '';
                data.forEach(item => {
                    addRow(item.name, item.target, item.weight);
                });
                calcTotalWeight();
            });
    });

    // Add manual row
    btnAdd.addEventListener('click', () => addRow());

    function addRow(name = '', target = '', weight = '') {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-2 py-2">
                <input type="text" name="criteria[${rowIndex}][name]" value="${name}" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" placeholder="Nhập tên tiêu chí">
            </td>
            <td class="px-2 py-2">
                <input type="text" name="criteria[${rowIndex}][target]" value="${target}" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" placeholder="Ví dụ: > 100tr">
            </td>
            <td class="px-2 py-2">
                <input type="number" name="criteria[${rowIndex}][weight]" value="${weight}" required min="0" max="100" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm weight-input" placeholder="0%">
            </td>
            <td class="px-2 py-2 text-center">
                <button type="button" class="text-red-500 hover:text-red-700 bg-red-50 p-1 rounded btn-remove"><i class="fas fa-times"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
        rowIndex++;

        tr.querySelector('.btn-remove').addEventListener('click', function() {
            tr.remove();
            calcTotalWeight();
        });

        tr.querySelector('.weight-input').addEventListener('input', calcTotalWeight);
    }
    
    function calcTotalWeight() {
        let total = 0;
        document.querySelectorAll('.weight-input').forEach(input => {
            total += parseFloat(input.value || 0);
        });
        
        const totEl = document.getElementById('total_weight');
        totEl.innerText = total.toFixed(1) + '%';
        totEl.className = `px-3 py-3 font-bold ${total > 100 || total < 100 ? 'text-red-600' : 'text-green-600'}`;
    }

    // Initial empty row
    if (tbody.children.length === 0) {
        addRow();
    }
});
</script>
@endpush
@endsection
