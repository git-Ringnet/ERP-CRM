@extends('layouts.app')

@php
    $isEdit = isset($template);
    $title = $isEdit ? 'Chỉnh sửa Mẫu Điều khoản: ' . $template->name : 'Tạo Mẫu Điều khoản Thanh toán';
@endphp

@section('title', $title)
@section('page-title', $title)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('settings.payment-templates.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại Danh sách
        </a>
    </div>

    @if ($errors->any())
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm text-sm text-red-700">
        <h4 class="font-bold mb-1">Có lỗi xảy ra:</h4>
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if (session('error'))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm text-sm text-red-700">
        {{ session('error') }}
    </div>
    @endif

    <form action="{{ $isEdit ? route('settings.payment-templates.update', $template->id) : route('settings.payment-templates.store') }}" method="POST" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- 1. General Config --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-base font-bold text-gray-900 flex items-center">
                    <i class="fas fa-cog mr-2 text-primary"></i> Thông tin cấu hình chung
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Tên mẫu điều khoản <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}" 
                           placeholder="VD: Cọc 30% - Thanh toán 70%" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả mẫu</label>
                    <textarea name="description" rows="2" placeholder="Nhập mô tả chi tiết về nghiệp vụ của mẫu điều khoản này..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary">{{ old('description', $template->description ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- 2. Milestones Dynamic Grid --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <div>
                    <h3 class="text-base font-bold text-gray-900 flex items-center">
                        <i class="fas fa-list-ol mr-2 text-primary"></i> Định nghĩa lộ trình & quy tắc chặn
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Tổng tỷ lệ % của các đợt phải bằng đúng 100%.</p>
                </div>
                <button type="button" id="btnAddMilestone" class="inline-flex items-center px-3 py-1.5 bg-indigo-550 text-indigo-700 bg-indigo-50 border border-indigo-200 text-xs font-bold rounded-lg hover:bg-indigo-100 transition-colors">
                    <i class="fas fa-plus mr-1"></i> Thêm đợt thanh toán
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm min-w-[1200px]" id="tblMilestones">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-xs border-b border-gray-200">
                            <th class="p-3 w-12 text-center">STT</th>
                            <th class="p-3 w-48">Tên đợt thanh toán</th>
                            <th class="p-3 w-28 text-right">Tỷ lệ (%)</th>
                            <th class="p-3 w-48">Sự kiện kích hoạt</th>
                            <th class="p-3 w-48">Bước nghiệp vụ bị chặn</th>
                            <th class="p-3 w-44">Cơ sở tính hạn</th>
                            <th class="p-3 w-28 text-right">Hạn (ngày)</th>
                            <th class="p-3 w-44">Chứng từ yêu cầu</th>
                            <th class="p-3 w-12 text-center">Xóa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="milestonesContainer">
                        {{-- Milestone rows will be inserted here dynamically --}}
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-gray-50/60 border-t border-gray-100 flex justify-between items-center">
                <div class="text-sm font-semibold text-gray-600">
                    Tổng cộng tỷ lệ: <span id="lblTotalPercentage" class="font-bold text-gray-900">0%</span>
                </div>
                <div id="percentageAlert" class="hidden text-xs font-bold text-red-500 flex items-center">
                    <i class="fas fa-exclamation-circle mr-1"></i> Tổng tỉ lệ phải bằng đúng 100%!
                </div>
            </div>
        </div>

        {{-- Submit Row --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('settings.payment-templates.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-lg transition-all border border-gray-200">Hủy bỏ</a>
            <button type="submit" id="btnSubmit" class="px-5 py-2 bg-primary text-white font-medium text-sm rounded-lg hover:bg-primary-dark transition-all shadow-sm">
                <i class="fas fa-save mr-2"></i> Lưu mẫu cấu hình
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('milestonesContainer');
    const btnAdd = document.getElementById('btnAddMilestone');
    const lblTotal = document.getElementById('lblTotalPercentage');
    const pctAlert = document.getElementById('percentageAlert');
    const btnSubmit = document.getElementById('btnSubmit');

    let milestoneIndex = 0;

    // Load initial data
    const initialItems = @json(old('items', $template->items ?? []));

    if (initialItems && initialItems.length > 0) {
        initialItems.forEach(item => {
            addMilestoneRow(item);
        });
    } else {
        // Add 1 default empty row
        addMilestoneRow();
    }

    btnAdd.addEventListener('click', () => addMilestoneRow());

    function addMilestoneRow(data = null) {
        const index = milestoneIndex++;
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-55/40 transition-colors border-b border-gray-100';
        row.id = `milestone-row-${index}`;

        const defaultName = data ? data.milestone_name : `Đợt ${container.children.length + 1}`;
        const percent = data ? parseFloat(data.percentage) : 0;
        const triggerType = data ? data.trigger_type : 'ON_CONTRACT_SIGNED';
        const blockingStage = data ? data.blocking_stage : '';
        const dueBase = data ? data.due_base : 'contract_date';
        const dueDays = data ? data.due_days : 0;
        const requiredDocs = data ? data.required_docs : 'none';

        row.innerHTML = `
            <td class="p-3 text-center text-xs font-bold text-gray-400 row-stt">${container.children.length + 1}</td>
            <td class="p-3">
                <input type="text" name="items[${index}][milestone_name]" value="${defaultName}" required
                       placeholder="VD: Cọc đợt 1..." class="w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:border-primary">
            </td>
            <td class="p-3">
                <div class="flex items-center">
                    <input type="number" name="items[${index}][percentage]" value="${percent}" required min="0" max="100" step="any"
                           class="milestone-percentage-input w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs text-right focus:ring-1 focus:ring-primary focus:border-primary">
                    <span class="ml-1 text-xs text-gray-500">%</span>
                </div>
            </td>
            <td class="p-3">
                <select name="items[${index}][trigger_type]" required class="w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:border-primary">
                    <option value="ON_CONTRACT_SIGNED" ${triggerType === 'ON_CONTRACT_SIGNED' ? 'selected' : ''}>Ký hợp đồng bán (Contract Signed)</option>
                    <option value="ON_DELIVERY_NOTICE" ${triggerType === 'ON_DELIVERY_NOTICE' ? 'selected' : ''}>Có thông báo giao hàng (Delivery Notice)</option>
                    <option value="BEFORE_EXPORT" ${triggerType === 'BEFORE_EXPORT' ? 'selected' : ''}>Trước khi xuất hàng (Before Export)</option>
                    <option value="ON_GOODS_DELIVERED" ${triggerType === 'ON_GOODS_DELIVERED' ? 'selected' : ''}>Bàn giao hàng hóa (Goods Delivered)</option>
                    <option value="ON_INVOICE_ISSUED" ${triggerType === 'ON_INVOICE_ISSUED' ? 'selected' : ''}>Xuất hóa đơn tài chính (Invoice Issued)</option>
                </select>
            </td>
            <td class="p-3">
                <select name="items[${index}][blocking_stage]" class="w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:border-primary">
                    <option value="" ${!blockingStage ? 'selected' : ''}>[Không chặn]</option>
                    <option value="BLOCK_PO_SEND" ${blockingStage === 'BLOCK_PO_SEND' ? 'selected' : ''}>Chặn đặt hàng Vendor (BLOCK_PO_SEND)</option>
                    <option value="BLOCK_WAREHOUSE_EXPORT" ${blockingStage === 'BLOCK_WAREHOUSE_EXPORT' ? 'selected' : ''}>Chặn xuất kho Logistics (BLOCK_WAREHOUSE_EXPORT)</option>
                    <option value="BLOCK_INVOICE" ${blockingStage === 'BLOCK_INVOICE' ? 'selected' : ''}>Chặn xuất hóa đơn Kế toán (BLOCK_INVOICE)</option>
                </select>
            </td>
            <td class="p-3">
                <select name="items[${index}][due_base]" required class="w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:border-primary">
                    <option value="contract_date" ${dueBase === 'contract_date' ? 'selected' : ''}>Ngày ký hợp đồng</option>
                    <option value="delivery_date" ${dueBase === 'delivery_date' ? 'selected' : ''}>Ngày bàn giao hàng</option>
                    <option value="invoice_date" ${dueBase === 'invoice_date' ? 'selected' : ''}>Ngày xuất hóa đơn</option>
                </select>
            </td>
            <td class="p-3">
                <div class="flex items-center">
                    <input type="number" name="items[${index}][due_days]" value="${dueDays}" required min="0"
                           class="w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs text-right focus:ring-1 focus:ring-primary focus:border-primary">
                    <span class="ml-1 text-xs text-gray-500">ngày</span>
                </div>
            </td>
            <td class="p-3">
                <select name="items[${index}][required_docs]" required class="w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:border-primary">
                    <option value="none" ${requiredDocs === 'none' ? 'selected' : ''}>Không yêu cầu chứng từ</option>
                    <option value="unc" ${requiredDocs === 'unc' ? 'selected' : ''}>Ủy nhiệm chi (UNC)</option>
                    <option value="credit_note" ${requiredDocs === 'credit_note' ? 'selected' : ''}>Giấy báo có ngân hàng</option>
                    <option value="cash_receipt" ${requiredDocs === 'cash_receipt' ? 'selected' : ''}>Phiếu thu tiền mặt</option>
                </select>
            </td>
            <td class="p-3 text-center">
                <button type="button" class="text-red-500 hover:text-red-700 btn-delete-row">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        container.appendChild(row);

        // Bind delete event
        row.querySelector('.btn-delete-row').addEventListener('click', function() {
            row.remove();
            reorderRows();
            calculateTotalPercentage();
        });

        // Bind input event on percentage
        row.querySelector('.milestone-percentage-input').addEventListener('input', calculateTotalPercentage);

        calculateTotalPercentage();
    }

    function reorderRows() {
        const rows = container.querySelectorAll('tr');
        rows.forEach((row, i) => {
            row.querySelector('.row-stt').textContent = i + 1;
        });
    }

    function calculateTotalPercentage() {
        const inputs = container.querySelectorAll('.milestone-percentage-input');
        let total = 0;
        inputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        lblTotal.textContent = `${total.toFixed(2)}%`;

        if (Math.abs(total - 100) > 0.01) {
            lblTotal.className = 'font-bold text-red-600';
            pctAlert.classList.remove('hidden');
        } else {
            lblTotal.className = 'font-bold text-green-600';
            pctAlert.classList.add('hidden');
        }
    }
});
</script>
@endsection
