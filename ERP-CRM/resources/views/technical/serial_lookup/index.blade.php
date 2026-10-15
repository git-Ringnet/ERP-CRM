@extends('layouts.app')

@section('title', 'Tra cứu S/N - License')
@section('page-title', 'Tra cứu Serial Number / License đã bán')

@section('content')
<div class="space-y-4">
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
            <div class="text-xs text-gray-500">
                @if($isLeader)
                    <i class="fas fa-user-shield text-indigo-500 mr-1"></i>Leader: xem toàn bộ S/N - license đã bán.
                @else
                    <i class="fas fa-user-gear text-blue-500 mr-1"></i>Chỉ hiển thị dữ liệu liên quan ticket / khách hàng bạn được giao.
                @endif
                Màn hình phục vụ hỗ trợ kỹ thuật, không hiển thị thông tin thương mại của đơn bán.
            </div>
        </div>
        <form method="GET" action="{{ route('technical.serial-lookup') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">S/N, mã hoặc tên sản phẩm</label>
                <input type="text" name="q" value="{{ request('q') }}" autofocus placeholder="Nhập / quét Serial Number..."
                    class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Khách hàng</label>
                <select name="customer_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả khách hàng</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Loại</label>
                <select name="type" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="serial" @selected(request('type') === 'serial')>Thiết bị có S/N</option>
                    <option value="license" @selected(request('type') === 'license')>License</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90">
                    <i class="fas fa-search mr-1"></i> Tra cứu
                </button>
                <a href="{{ route('technical.serial-lookup') }}" class="px-3 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50" title="Xóa lọc">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase">
                    <tr>
                        <th class="px-3 py-2.5 text-left">Serial Number</th>
                        <th class="px-3 py-2.5 text-left">Sản phẩm</th>
                        <th class="px-3 py-2.5 text-left">License</th>
                        <th class="px-3 py-2.5 text-left">Khách hàng / Dự án</th>
                        <th class="px-3 py-2.5 text-left">Phiếu xuất</th>
                        <th class="px-3 py-2.5 text-left">Bảo hành / Hết hạn</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $item)
                        @php
                            $export = $item->export;
                            $customerName = $export?->customer?->name ?: ($export?->sale?->customer_name ?: '');
                            $warrantyEnd = $item->expiry_date
                                ?: (($item->warranty_months && $export?->date) ? $export->date->copy()->addMonths($item->warranty_months) : null);
                            $licenseType = $item->custom_fields['license_type'] ?? null;
                            $licenseFiles = $item->license_files;
                        @endphp
                        <tr class="hover:bg-gray-50/60 align-top">
                            <td class="px-3 py-2 font-mono font-semibold text-gray-900 whitespace-nowrap">
                                {{ $item->isNoSku() ? '(Không S/N)' : $item->sku }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-800">{{ $item->product?->code }}</div>
                                <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($item->product?->name, 80) }}</div>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                @if($licenseType)
                                    <span class="inline-block px-2 py-0.5 rounded-full font-semibold {{ $licenseType === 'renewal' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $licenseType === 'renewal' ? 'Renewal' : 'New buy' }}
                                    </span>
                                @endif
                                @foreach($licenseFiles as $lf)
                                    <div class="text-gray-600 mt-0.5"><i class="fas fa-file-alt text-gray-400 mr-1"></i>{{ $lf['name'] }}</div>
                                @endforeach
                                @if(!$licenseType && empty($licenseFiles))
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="text-gray-800">{{ $customerName ?: '-' }}</div>
                                @if($export?->project)
                                    <div class="text-xs text-gray-500">{{ $export->project->name }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs">
                                <div class="font-semibold text-gray-700">{{ $export?->code }}</div>
                                <div class="text-gray-500">{{ $export?->date?->format('d/m/Y') }}</div>
                                @if($export?->sale)
                                    <div class="text-gray-500">Đơn: {{ $export->sale->code }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs">
                                @if($warrantyEnd)
                                    <span class="{{ $warrantyEnd->isPast() ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                        {{ $warrantyEnd->format('d/m/Y') }}{{ $warrantyEnd->isPast() ? ' (Hết hạn)' : '' }}
                                    </span>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                                @if($item->warranty_months)
                                    <div class="text-gray-400">{{ $item->warranty_months }} tháng</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-10 text-center text-gray-400">
                                <i class="fas fa-barcode text-3xl mb-2 block"></i>
                                Không tìm thấy Serial Number / license phù hợp{{ $isLeader ? '' : ' trong phạm vi ticket / khách hàng bạn được giao' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection
