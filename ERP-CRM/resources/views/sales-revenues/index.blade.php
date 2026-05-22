@extends('layouts.app')

@section('title', 'Theo dõi Doanh số & Thanh toán')
@section('page-title', 'THEO DÕI DOANH SỐ & THANH TOÁN VỚI FORTINET')

@section('content')
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>Dữ liệu tự động tổng hợp từ PO & SO. Click vào ô để sửa trực tiếp.
            </div>
            <div class="flex flex-wrap gap-2">
                @can('create_sales_revenues')
                    <form method="POST" action="{{ route('sales-revenues.sync') }}" class="inline">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="supplier_id" value="{{ $supplierId }}">
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            <i class="fas fa-sync-alt mr-2"></i>Đồng bộ từ PO
                        </button>
                    </form>
                @endcan
                <a href="{{ route('sales-revenues.export', request()->query()) }}"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded-md hover:bg-green-600">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </a>
                <button onclick="window.location.reload()"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                    <i class="fas fa-redo mr-2"></i>Làm mới
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Năm</label>
                    <select name="year" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor / Hãng</label>
                    <select name="supplier_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                        <option value="">Tất cả</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="PO, sản phẩm, khách hàng, CPQ, S/N..."
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-1.5 bg-primary text-white rounded-md hover:bg-primary-dark text-sm font-medium">
                        <i class="fas fa-filter mr-2"></i>Lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-gradient-to-br from-[#e87722] to-[#d06a1e] text-white rounded-lg shadow-sm p-3">
                <p class="text-xs opacity-80">Tổng dòng</p>
                <p class="text-xl font-bold">{{ number_format($stats['total_records']) }}</p>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-sm p-3">
                <p class="text-xs opacity-80">Tổng SL</p>
                <p class="text-xl font-bold">{{ number_format($stats['total_quantity']) }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-sm p-3">
                <p class="text-xs opacity-80">Thành Tiền (PO)</p>
                <p class="text-xl font-bold">${{ number_format($stats['total_amount'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-sm p-3">
                <p class="text-xs opacity-80">Giá Bán (SO)</p>
                <p class="text-xl font-bold">{{ number_format($stats['total_selling'], 0, ',', '.') }}đ</p>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-6 text-xs text-gray-500">
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded border border-gray-300" style="background:#f0f4f8"></span>
                Dữ liệu tự động (PO/SO) — không cần điền
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded border border-gray-300 bg-white"></span>
                Điền trực tiếp — click để sửa
            </span>
        </div>

        <!-- Spreadsheet Table -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="overflow-x-auto" id="tableWrapper">
                <table class="w-full text-xs border-collapse min-w-[2400px]" id="revenueTable">
                    <thead>
                        <tr class="bg-[#e87722] text-white text-center text-[10px]">
                            <th class="px-1 py-2 border border-[#d06a1e] w-8 sticky left-0 bg-[#e87722] z-20">STT</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[80px]">CPQ</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[100px]">Tình trạng<br/>XHĐ</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[100px]">Hàng đã nhập<br/>kho (WH)</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[90px]">Đã Xuất POS<br/>(License)</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[90px]">Số PO</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[80px]">Ngày PO</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[160px]">Hàng hóa</th>
                            <th class="px-1 py-2 border border-[#d06a1e] w-10">SL</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[90px]">S.N</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[80px]">Quote ID</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[80px]">ListPrice</th>
                            <th class="px-1 py-2 border border-[#d06a1e] w-14">Discount</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[80px]">Unit Price</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[90px]">Thành Tiền</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[80px]">Expired<br/>date</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[130px]">Khách hàng</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[90px]">Giá bán</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[140px]">End User/ Partner<br/>(Project)</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[90px] ">Equipment</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[90px]">Partner</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[90px]">EU</th>
                            <th class="px-1 py-2 border border-[#d06a1e] min-w-[80px] ">Industries</th>
                            <th class="px-1 py-2 border border-[#d06a1e] w-8">
                                <i class="fas fa-cog"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($revenues as $i => $rev)
                            <tr class="hover:bg-orange-50/50 transition-colors border-b border-gray-200" data-id="{{ $rev->id }}">
                                {{-- STT --}}
                                <td class="td-auto px-1 py-1 text-center font-medium text-gray-500 sticky left-0 z-10">
                                    {{ ($revenues->currentPage() - 1) * $revenues->perPage() + $i + 1 }}
                                </td>

                                {{-- CPQ (MANUAL - white) --}}
                                <td class="td-manual cell-edit" data-field="cpq_number" data-id="{{ $rev->id }}">
                                    <span class="cell-value">{{ $rev->cpq_number }}</span>
                                </td>

                                {{-- Tình trạng XHĐ (AUTO from InvoiceRequest) --}}
                                <td class="td-auto cell-edit-select" data-field="invoice_status" data-id="{{ $rev->id }}">
                                    @if($rev->invoice_status === 'official_issued')
                                        <span class="cell-value px-1 py-0.5 bg-green-100 text-green-800 rounded text-[10px]">Đã xuất chính thức</span>
                                    @elseif($rev->invoice_status === 'draft_issued')
                                        <span class="cell-value px-1 py-0.5 bg-blue-100 text-blue-800 rounded text-[10px]">Đã xuất nháp</span>
                                    @elseif($rev->invoice_status === 'pending')
                                        <span class="cell-value px-1 py-0.5 bg-yellow-100 text-yellow-800 rounded text-[10px]">Chờ xử lý</span>
                                    @elseif($rev->invoice_status === 'rejected')
                                        <span class="cell-value px-1 py-0.5 bg-red-100 text-red-800 rounded text-[10px]">Bị từ chối</span>
                                    @elseif($rev->invoice_status === 'not_issued')
                                        <span class="cell-value px-1 py-0.5 bg-gray-200 text-gray-600 rounded text-[10px]">Chưa xuất</span>
                                    @else
                                        <span class="cell-value text-gray-400 italic">—</span>
                                    @endif
                                </td>

                                {{-- Hàng nhập kho (AUTO from PO) --}}
                                <td class="td-auto cell-edit" data-field="warehouse_status" data-id="{{ $rev->id }}">
                                    @if($rev->warehouse_status === 'Đã nhập đủ')
                                        <span class="cell-value px-1 py-0.5 bg-green-100 text-green-800 rounded text-[10px]">{{ $rev->warehouse_status }}</span>
                                    @elseif($rev->warehouse_status)
                                        <span class="cell-value px-1 py-0.5 bg-yellow-100 text-yellow-800 rounded text-[10px]">{{ $rev->warehouse_status }}</span>
                                    @else
                                        <span class="cell-value text-gray-400 italic">—</span>
                                    @endif
                                </td>

                                {{-- License (AUTO from PO item) --}}
                                <td class="td-auto cell-edit" data-field="license_exported" data-id="{{ $rev->id }}">
                                    @if($rev->license_exported === 'Đã xuất')
                                        <span class="cell-value px-1 py-0.5 bg-green-100 text-green-800 rounded text-[10px]">Đã xuất</span>
                                    @elseif($rev->license_exported)
                                        <span class="cell-value px-1 py-0.5 bg-gray-200 text-gray-600 rounded text-[10px]">{{ $rev->license_exported }}</span>
                                    @else
                                        <span class="cell-value text-gray-400 italic">—</span>
                                    @endif
                                </td>

                                {{-- Số PO (AUTO, read-only, linked) --}}
                                <td class="td-auto px-1 py-1 text-center font-mono text-[10px]">
                                    @if($rev->purchase_order_id)
                                        <a href="{{ route('purchase-orders.show', $rev->purchase_order_id) }}" class="text-primary hover:underline font-medium" title="Xem PO">
                                            {{ $rev->po_code }}
                                        </a>
                                    @else
                                        {{ $rev->po_code }}
                                    @endif
                                </td>

                                {{-- Ngày PO (AUTO) --}}
                                <td class="td-auto px-1 py-1 text-center text-[10px]">
                                    {{ $rev->po_date?->format('d/m/Y') }}
                                </td>

                                {{-- Hàng hóa (AUTO) --}}
                                <td class="td-auto px-1 py-1 font-medium text-[10px]" title="{{ $rev->product_name }}">
                                    <div class="max-w-[160px] truncate">{{ $rev->product_name }}</div>
                                </td>

                                {{-- SL (AUTO) --}}
                                <td class="td-auto px-1 py-1 text-center font-bold text-[10px]">
                                    {{ $rev->quantity }}
                                </td>

                                {{-- S.N (AUTO from SOR item) --}}
                                <td class="td-auto cell-edit" data-field="serial_number" data-id="{{ $rev->id }}">
                                    <span class="cell-value">{{ $rev->serial_number ?: '' }}</span>
                                </td>

                                {{-- Quote ID (AUTO from Quotation) --}}
                                <td class="td-auto cell-edit" data-field="quote_id" data-id="{{ $rev->id }}">
                                    <span class="cell-value">{{ $rev->quote_id ?: '' }}</span>
                                </td>

                                {{-- ListPrice (AUTO from SaleItem.usd_price) --}}
                                <td class="td-auto cell-edit text-right" data-field="list_price" data-id="{{ $rev->id }}" data-type="number">
                                    <span class="cell-value">{{ $rev->list_price > 0 ? number_format($rev->list_price, 2) : '' }}</span>
                                </td>

                                {{-- Discount (AUTO from SaleItem.discount_rate) --}}
                                <td class="td-auto cell-edit text-center" data-field="discount_percent" data-id="{{ $rev->id }}" data-type="number">
                                    <span class="cell-value">{{ $rev->discount_percent > 0 ? $rev->discount_percent . '%' : '' }}</span>
                                </td>

                                {{-- Unit Price (AUTO from PO) --}}
                                <td class="td-auto px-1 py-1 text-right font-medium text-[10px]">
                                    @if($rev->unit_price > 0)
                                        ${{ number_format($rev->unit_price, 2) }}
                                    @endif
                                </td>

                                {{-- Thành Tiền (AUTO from PO) --}}
                                <td class="td-auto px-1 py-1 text-right font-bold text-primary text-[10px]">
                                    @if($rev->total_amount > 0)
                                        ${{ number_format($rev->total_amount, 2) }}
                                    @endif
                                </td>

                                {{-- Expired date (AUTO from SOR item) --}}
                                <td class="td-auto cell-edit text-center" data-field="expired_date" data-id="{{ $rev->id }}" data-type="date">
                                    <span class="cell-value {{ $rev->expired_date && $rev->expired_date->isPast() ? 'text-red-600 font-bold' : '' }}">
                                        {{ $rev->expired_date?->format('d/m/Y') ?: '' }}
                                    </span>
                                </td>

                                {{-- Khách hàng (AUTO from Sale) --}}
                                <td class="td-auto px-1 py-1 text-[10px]">
                                    @if($rev->customer_id)
                                        <a href="{{ route('customers.show', $rev->customer_id) }}" class="text-primary hover:underline">
                                            {{ $rev->customer_name }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">{{ $rev->customer_name ?: '—' }}</span>
                                    @endif
                                </td>

                                {{-- Giá bán (AUTO from SaleItem.price) --}}
                                <td class="td-auto cell-edit text-right" data-field="selling_price" data-id="{{ $rev->id }}" data-type="number">
                                    <span class="cell-value">{{ $rev->selling_price > 0 ? number_format($rev->selling_price, 0, ',', '.') : '' }}</span>
                                </td>

                                {{-- End User/Partner (AUTO from Project) --}}
                                <td class="td-auto cell-edit" data-field="end_user_partner" data-id="{{ $rev->id }}">
                                    <span class="cell-value"><div class="max-w-[140px] truncate" title="{{ $rev->end_user_partner }}">{{ $rev->end_user_partner ?: '' }}</div></span>
                                </td>

                                {{-- Equipment (MANUAL - white) --}}
                                <td class="td-manual cell-edit" data-field="equipment" data-id="{{ $rev->id }}">
                                    <span class="cell-value">{{ $rev->equipment ?: '' }}</span>
                                </td>

                                {{-- Partner / SI Name (AUTO from SOR item) --}}
                                <td class="td-auto cell-edit" data-field="partner_name" data-id="{{ $rev->id }}">
                                    <span class="cell-value">{{ $rev->partner_name ?: '' }}</span>
                                </td>

                                {{-- EU / EU Name-MST (AUTO from SOR item) --}}
                                <td class="td-auto cell-edit" data-field="end_user" data-id="{{ $rev->id }}">
                                    <span class="cell-value">{{ $rev->end_user ?: '' }}</span>
                                </td>

                                {{-- Industries (MANUAL - white) --}}
                                <td class="td-manual cell-edit" data-field="industry" data-id="{{ $rev->id }}">
                                    <span class="cell-value">{{ $rev->industry ?: '' }}</span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-1 py-1 text-center bg-white">
                                    @can('delete_sales_revenues')
                                        <form method="POST" action="{{ route('sales-revenues.destroy', $rev) }}" class="inline"
                                            onsubmit="return confirm('Xóa dòng này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-600 text-[10px]" title="Xóa">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="24" class="px-4 py-12 text-center text-gray-400">
                                    <i class="fas fa-inbox text-4xl text-gray-200 mb-3 block"></i>
                                    <p class="text-sm">Chưa có dữ liệu doanh số</p>
                                    <p class="text-xs mt-1">Nhấn <strong>"Đồng bộ từ PO"</strong> để tự động lấy dữ liệu từ Đơn đặt hàng</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($revenues->count() > 0)
                        <tfoot>
                            <tr class="bg-orange-50 font-bold text-[10px]">
                                <td colspan="8" class="px-2 py-2 text-right border border-gray-300 sticky left-0 bg-orange-50 z-10">TỔNG CỘNG</td>
                                <td class="px-1 py-2 text-center border border-gray-300">{{ number_format($stats['total_quantity']) }}</td>
                                <td colspan="5" class="border border-gray-300"></td>
                                <td class="px-1 py-2 text-right border border-gray-300 text-primary">${{ number_format($stats['total_amount'], 2) }}</td>
                                <td class="border border-gray-300"></td>
                                <td class="border border-gray-300"></td>
                                <td class="px-1 py-2 text-right border border-gray-300">{{ number_format($stats['total_selling'], 0, ',', '.') }}đ</td>
                                <td colspan="6" class="border border-gray-300"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @if($revenues->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $revenues->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('styles')
    <style>
        /* ─── AUTO cells: light blue-gray tint ─── */
        .td-auto {
            background-color: #f0f4f8 !important;
            border-right: 1px solid #dde3ea;
            font-size: 10px;
        }
        /* ─── MANUAL cells: white background ─── */
        .td-manual {
            background-color: #ffffff !important;
            border-right: 1px solid #e5e7eb;
            font-size: 10px;
        }
        /* Sticky column inherits auto bg */
        tr:hover .td-auto.sticky { background-color: #fef3e2 !important; }
        tr:hover .td-auto { background-color: #fef3e2 !important; }
        tr:hover .td-manual { background-color: #fff7ed !important; }

        /* Editable cell hover indicator */
        .cell-edit, .cell-edit-select {
            padding: 2px 4px;
            cursor: pointer;
            min-height: 24px;
            position: relative;
        }
        .td-manual.cell-edit:hover {
            outline: 2px solid #e87722;
            outline-offset: -2px;
            background-color: #fffbf0 !important;
        }
        .td-auto.cell-edit:hover, .td-auto.cell-edit-select:hover {
            outline: 1px dashed #94a3b8;
            outline-offset: -1px;
        }
        .cell-edit input, .cell-edit-select select {
            width: 100%;
            padding: 1px 3px;
            font-size: 10px;
            border: 1px solid #e87722;
            border-radius: 2px;
            outline: none;
            background: #FFFBF0;
        }
        .cell-edit input:focus, .cell-edit-select select:focus {
            box-shadow: 0 0 0 2px rgba(232, 119, 34, 0.25);
        }

        /* Manual header highlight */
        .th-manual {
            background-color: #d06a1e !important;
            position: relative;
        }
        .th-manual::after {
            content: '✏️';
            font-size: 8px;
            display: block;
            opacity: 0.8;
        }

        /* Save animations */
        .cell-saving { opacity: 0.6; }
        .cell-saved { animation: cellSaved 0.8s ease-out; }
        @keyframes cellSaved {
            0% { background-color: #D1FAE5; }
            100% { background-color: transparent; }
        }
        .cell-error { animation: cellError 0.8s ease-out; }
        @keyframes cellError {
            0% { background-color: #FEE2E2; }
            100% { background-color: transparent; }
        }
        /* Sticky first column */
        #revenueTable thead th:first-child { z-index: 20; }
    </style>
    @endpush

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            // ─── Inline Text Edit ───────────────────────────────────
            document.querySelectorAll('.cell-edit').forEach(cell => {
                cell.addEventListener('click', function(e) {
                    if (this.querySelector('input')) return;

                    const valueEl = this.querySelector('.cell-value');
                    const field = this.dataset.field;
                    const id = this.dataset.id;
                    const type = this.dataset.type || 'text';

                    let rawValue = '';
                    if (valueEl) {
                        rawValue = valueEl.textContent.trim();
                        if (type === 'number') {
                            rawValue = rawValue.replace(/[^0-9.,\-]/g, '').replace(/\./g, '').replace(',', '.');
                        }
                        if (rawValue === '—') rawValue = '';
                    }

                    const origHtml = this.innerHTML;

                    let input;
                    if (type === 'date') {
                        input = document.createElement('input');
                        input.type = 'date';
                        if (rawValue && rawValue.includes('/')) {
                            const parts = rawValue.split('/');
                            input.value = `${parts[2]}-${parts[1]}-${parts[0]}`;
                        } else {
                            input.value = rawValue;
                        }
                    } else {
                        input = document.createElement('input');
                        input.type = type === 'number' ? 'number' : 'text';
                        input.step = type === 'number' ? '0.01' : undefined;
                        input.value = rawValue;
                    }

                    this.innerHTML = '';
                    this.appendChild(input);
                    input.focus();
                    input.select();

                    const saveAndRestore = async () => {
                        const newValue = input.value;
                        cell.classList.add('cell-saving');

                        try {
                            const resp = await fetch(`/sales-revenues/${id}/cell`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ field, value: newValue }),
                            });

                            const result = await resp.json();
                            cell.classList.remove('cell-saving');

                            if (result.success) {
                                let displayValue = newValue;
                                if (field === 'selling_price' && newValue) {
                                    displayValue = parseInt(newValue).toLocaleString('vi-VN');
                                } else if (field === 'list_price' && newValue) {
                                    displayValue = parseFloat(newValue).toLocaleString('en-US', {minimumFractionDigits: 2});
                                } else if (field === 'discount_percent' && newValue) {
                                    displayValue = newValue + '%';
                                } else if (type === 'date' && newValue) {
                                    const d = new Date(newValue);
                                    displayValue = d.toLocaleDateString('vi-VN');
                                }

                                cell.innerHTML = `<span class="cell-value">${displayValue || ''}</span>`;
                                cell.classList.add('cell-saved');
                                setTimeout(() => cell.classList.remove('cell-saved'), 800);
                            } else {
                                cell.innerHTML = origHtml;
                                cell.classList.add('cell-error');
                                setTimeout(() => cell.classList.remove('cell-error'), 800);
                            }
                        } catch (err) {
                            console.error('Save error:', err);
                            cell.innerHTML = origHtml;
                            cell.classList.remove('cell-saving');
                            cell.classList.add('cell-error');
                            setTimeout(() => cell.classList.remove('cell-error'), 800);
                        }
                    };

                    input.addEventListener('blur', saveAndRestore);
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
                        if (e.key === 'Escape') { cell.innerHTML = origHtml; }
                    });
                });
            });

            // ─── Inline Select Edit (Invoice Status) ─────────────────
            document.querySelectorAll('.cell-edit-select').forEach(cell => {
                cell.addEventListener('click', function(e) {
                    if (this.querySelector('select')) return;

                    const id = this.dataset.id;
                    const field = this.dataset.field;
                    const origHtml = this.innerHTML;

                    const statuses = {!! json_encode(\App\Models\SalesRevenue::INVOICE_STATUSES) !!};

                    const select = document.createElement('select');
                    select.innerHTML = '<option value="">Chọn...</option>';
                    Object.entries(statuses).forEach(([key, label]) => {
                        select.innerHTML += `<option value="${key}">${label}</option>`;
                    });

                    this.innerHTML = '';
                    this.appendChild(select);
                    select.focus();

                    const saveSelect = async () => {
                        const newValue = select.value;
                        cell.classList.add('cell-saving');

                        try {
                            const resp = await fetch(`/sales-revenues/${id}/cell`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ field, value: newValue }),
                            });

                            const result = await resp.json();
                            cell.classList.remove('cell-saving');

                            if (result.success) {
                                const label = statuses[newValue] || '—';
                                const colors = {
                                    'official_issued': 'bg-green-100 text-green-800',
                                    'draft_issued': 'bg-blue-100 text-blue-800',
                                    'pending': 'bg-yellow-100 text-yellow-800',
                                    'not_issued': 'bg-gray-200 text-gray-600',
                                    'rejected': 'bg-red-100 text-red-800',
                                };
                                const colorClass = colors[newValue] || 'text-gray-300';
                                if (newValue) {
                                    cell.innerHTML = `<span class="cell-value px-1 py-0.5 ${colorClass} rounded text-[10px]">${label}</span>`;
                                } else {
                                    cell.innerHTML = '<span class="cell-value text-gray-400 italic">—</span>';
                                }
                                cell.classList.add('cell-saved');
                                setTimeout(() => cell.classList.remove('cell-saved'), 800);
                            } else {
                                cell.innerHTML = origHtml;
                                cell.classList.add('cell-error');
                                setTimeout(() => cell.classList.remove('cell-error'), 800);
                            }
                        } catch (err) {
                            cell.innerHTML = origHtml;
                            cell.classList.remove('cell-saving');
                        }
                    };

                    select.addEventListener('change', saveSelect);
                    select.addEventListener('blur', function() {
                        setTimeout(() => {
                            if (!cell.querySelector('select')) return;
                            cell.innerHTML = origHtml;
                        }, 200);
                    });
                });
            });
        });
    </script>
@endsection
