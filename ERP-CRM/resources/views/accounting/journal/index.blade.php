@extends('layouts.app')

@section('title', 'Nhật ký kế toán kho')
@section('page-title', 'Nhật ký kế toán kho')

@section('content')
<div class="space-y-6">
    {{-- Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" action="{{ route('accounting.journal.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                    class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                    class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex-1 min-w-[130px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Loại phiếu</label>
                <select name="type" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tất cả</option>
                    <option value="import" {{ $type === 'import' ? 'selected' : '' }}>Nhập kho</option>
                    <option value="export" {{ $type === 'export' ? 'selected' : '' }}>Xuất kho</option>
                    <option value="transfer" {{ $type === 'transfer' ? 'selected' : '' }}>Chuyển kho</option>
                </select>
            </div>
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Tìm kiếm</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Mã phiếu, TK, mô tả..."
                    class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                    <i class="fas fa-search mr-1"></i> Lọc
                </button>
                <a href="{{ route('accounting.journal.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    <i class="fas fa-redo mr-1"></i> Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-book text-blue-600"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Tổng bút toán</p>
                <p class="text-lg font-bold text-gray-800">{{ $entries->total() }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-dollar-sign text-green-600"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Tổng giá trị</p>
                <p class="text-lg font-bold text-gray-800">{{ number_format($totalAmount, 0, ',', '.') }} đ</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-calendar text-purple-600"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Kỳ xem</p>
                <p class="text-sm font-semibold text-gray-800">{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Số phiếu</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Loại</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Số tiền</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nội dung</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Người tạo</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                {{ $entry->entry_date->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @php
                                    $routeName = match($entry->reference_type) {
                                        'import' => 'imports.show',
                                        'export' => 'exports.show',
                                        'transfer' => 'transfers.show',
                                        default => null,
                                    };
                                @endphp
                                @if($routeName)
                                    <a href="{{ route($routeName, $entry->reference_id) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                        {{ $entry->reference_code }}
                                    </a>
                                @else
                                    <span class="font-medium">{{ $entry->reference_code }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @php
                                    $badgeColor = match($entry->reference_type) {
                                        'import' => 'bg-blue-100 text-blue-700',
                                        'export' => 'bg-orange-100 text-orange-700',
                                        'transfer' => 'bg-purple-100 text-purple-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                    $icon = match($entry->reference_type) {
                                        'import' => 'fa-arrow-down',
                                        'export' => 'fa-arrow-up',
                                        'transfer' => 'fa-exchange-alt',
                                        default => 'fa-file',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $badgeColor }}">
                                    <i class="fas {{ $icon }} mr-1"></i>
                                    {{ $entry->type_label }}
                                </span>
                                @if($entry->sub_type_label)
                                    <span class="text-xs text-gray-400 ml-1">({{ $entry->sub_type_label }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-800 whitespace-nowrap">
                                {{ number_format($entry->amount, 0, ',', '.') }} đ
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $entry->description }}">
                                {{ $entry->description }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                {{ $entry->created_by }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                <i class="fas fa-book-open text-4xl mb-3 block"></i>
                                <p class="text-lg font-medium">Chưa có nhật ký vận chuyển</p>
                                <p class="text-sm mt-1">Sẽ được tạo tự động khi phiếu nhập/xuất/chuyển kho được duyệt.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
