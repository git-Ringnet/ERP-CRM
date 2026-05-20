@extends('layouts.app')

@section('content')
<div class="">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Danh sách Yêu cầu đặt hàng (PR) đã xóa</h1>
            <p class="text-sm text-gray-600">Thùng rác quản lý các yêu cầu PR đã bị xóa mềm</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('purchase-requests.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center shadow-md">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách PR
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('purchase-requests.deleted') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Mã PR</label>
                <input type="text" name="code" value="{{ request('code') }}" placeholder="SOR..." class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Mã SO</label>
                <input type="text" name="sale_code" value="{{ request('sale_code') }}" placeholder="SO..." class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Ghi chú</label>
                <input type="text" name="note" value="{{ request('note') }}" placeholder="Tìm trong ghi chú..." class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
            </div>
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors text-sm shadow-sm">
                <i class="fas fa-search mr-1"></i> Lọc dữ liệu
            </button>
            <a href="{{ route('purchase-requests.deleted') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                Xóa lọc
            </a>
        </form>
    </div>

    <!-- PR Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Mã PR</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Đơn bán hàng (SO)</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Hãng</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Người yêu cầu</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Thời gian xóa</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Người xóa</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Lý do xóa</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $request)
                <tr class="hover:bg-gray-50 transition-colors bg-red-50 bg-opacity-20">
                    <td class="px-6 py-4 font-bold text-red-700">#{{ $request->code }}</td>
                    <td class="px-6 py-4">
                        <span class="text-gray-700 font-medium">
                            {{ $request->sale->code ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1">
                            @php
                                $uniqueVendors = $request->items->pluck('vendor')->unique()->filter();
                                if ($uniqueVendors->isEmpty()) {
                                    $uniqueVendors = $request->items->map(fn($i) => $i->vendor->name ?? null)->unique()->filter();
                                }
                            @endphp
                            <div class="text-sm font-medium text-gray-800">
                                {{ $uniqueVendors->implode(', ') ?: 'N/A' }}
                            </div>
                            @if($request->attachments->count() > 0)
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($request->attachments as $attachment)
                                <span class="inline-flex items-center text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded cursor-not-allowed"
                                   title="{{ $attachment->file_name }} (Đã xóa)">
                                    <i class="fas fa-paperclip mr-1"></i> {{ \Illuminate\Support\Str::limit($attachment->file_name, 15) }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $request->creator->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $request->deleted_at ? $request->deleted_at->format('d/m/Y H:i') : 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-700">
                        {{ $request->deleteLog->user_name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-red-600 italic max-w-xs truncate" title="{{ $request->delete_reason }}">
                        {{ $request->delete_reason ?: 'Không có lý do' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <form action="{{ route('purchase-requests.restore', $request->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="button" onclick="confirmRestore(this.form, '{{ $request->code }}')" class="bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors inline-flex items-center shadow-sm" title="Khôi phục PR">
                                    <i class="fas fa-undo mr-1.5"></i> Khôi phục
                                </button>
                            </form>

                            <button type="button" onclick="toggleDetails('{{ $request->id }}')" class="text-gray-400 hover:text-gray-600 p-1">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <!-- Details Row (Hidden by default) -->
                <tr id="details-{{ $request->id }}" class="hidden bg-gray-50">
                    <td colspan="8" class="px-6 py-4">
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <h4 class="font-bold text-sm mb-3 text-gray-700">Chi tiết sản phẩm yêu cầu (Đã xóa):</h4>
                            <table class="w-full text-[10px] border-collapse border border-gray-200">
                                <thead class="bg-yellow-50">
                                    <tr class="border-b border-gray-300">
                                        <th class="border-r border-gray-300 p-2 text-left">Hãng</th>
                                        <th class="border-r border-gray-300 p-2 text-left">Sản phẩm</th>
                                        <th class="border-r border-gray-300 p-2 text-center">Số lượng</th>
                                        <th class="border-r border-gray-300 p-2 text-center">% Lợi nhuận</th>
                                        <th class="border-r border-gray-300 p-2 text-left">S/N (Nếu có)</th>
                                        <th class="border-r border-gray-300 p-2 text-left">Ngày Exp (Nếu có)</th>
                                        <th class="border-r border-gray-300 p-2 text-left">SI Name</th>
                                        <th class="border-r border-gray-300 p-2 text-left">EU Name</th>
                                        <th class="p-2 text-left">Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($request->items as $item)
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="border-r border-gray-200 p-2">{{ $item->vendor->name ?? $item->vendor }}</td>
                                        <td class="border-r border-gray-200 p-2 font-medium text-teal-700">
                                            {{ $item->part_number }}
                                        </td>
                                        <td class="border-r border-gray-200 p-2 text-center font-bold">{{ $item->quantity + 0 }}</td>
                                        <td class="border-r border-gray-200 p-2 text-center text-blue-600">
                                            {{ number_format($item->saleItem->profit_percent ?? 0, 2) }}%
                                        </td>
                                        <td class="border-r border-gray-200 p-2 text-gray-500">{{ $item->serial_number ?: '-' }}</td>
                                        <td class="border-r border-gray-200 p-2 text-gray-500">{{ $item->exp_date ? $item->exp_date->format('d/m/Y') : '-' }}</td>
                                        <td class="border-r border-gray-200 p-2 text-gray-700">{{ $item->si_name }}</td>
                                        <td class="border-r border-gray-200 p-2 text-gray-600">{{ $item->eu_name_mst }}</td>
                                        <td class="p-2 text-gray-500">{{ $item->note ?: '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if($request->note)
                            <div class="mt-3 p-2 bg-yellow-50 rounded border border-yellow-100 text-xs text-yellow-800">
                                <strong>Ghi chú từ Sales:</strong> {{ $request->note }}
                            </div>
                            @endif
                            @if($request->rejection_note)
                            <div class="mt-3 p-2 bg-red-50 rounded border border-red-100 text-xs text-red-800">
                                <strong>Lý do trả về:</strong> {{ $request->rejection_note }}
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-trash-alt text-4xl mb-3 text-gray-300"></i>
                        <p>Thùng rác trống. Không có yêu cầu đặt hàng nào đã xóa.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>

@push('scripts')
<script>
    function toggleDetails(id) {
        const row = document.getElementById('details-' + id);
        row.classList.toggle('hidden');
    }

    function confirmRestore(form, code) {
        Swal.fire({
            title: 'Khôi phục Yêu cầu?',
            text: `Bạn có chắc chắn muốn khôi phục Yêu cầu đặt hàng #${code} về lại danh sách đang hoạt động?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2ecc71',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Khôi phục ngay',
            cancelButtonText: 'Hủy',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>
@endpush
@endsection
