@extends('layouts.app')

@section('title', 'Báo cáo tài sản nội bộ')
@section('page-title', 'Báo cáo Tổng hợp Tài sản Nội bộ')

@section('content')
<div class="space-y-4">

    {{-- Actions --}}
    <div class="flex justify-between items-center">
        <div class="flex gap-2">
            <a href="{{ route('employee-assets.index') }}"
                class="inline-flex items-center px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 shadow-sm">
                <i class="fas fa-box mr-2"></i>Danh mục tài sản
            </a>
            <a href="{{ route('employee-asset-assignments.index') }}"
                class="inline-flex items-center px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 shadow-sm">
                <i class="fas fa-exchange-alt mr-2"></i>Cấp phát
            </a>
        </div>
        <a href="{{ route('employee-asset-reports.export') }}"
            class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm">
            <i class="fas fa-file-excel mr-2"></i>Xuất Excel
        </a>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $totalAssets }}</div>
            <div class="text-xs text-gray-500 mt-1">Tổng tài sản</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center border-l-4 border-green-400">
            <div class="text-2xl font-bold text-green-600">{{ $totalAvailable }}</div>
            <div class="text-xs text-gray-500 mt-1">Sẵn sàng</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center border-l-4 border-blue-400">
            <div class="text-2xl font-bold text-blue-600">{{ $totalAssigned }}</div>
            <div class="text-xs text-gray-500 mt-1">Đang cấp phát</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center border-l-4 border-yellow-400">
            <div class="text-2xl font-bold text-yellow-600">{{ $totalMaintenance }}</div>
            <div class="text-xs text-gray-500 mt-1">Bảo trì</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center border-l-4 border-gray-400">
            <div class="text-2xl font-bold text-gray-500">{{ $totalDisposed }}</div>
            <div class="text-xs text-gray-500 mt-1">Thanh lý</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center border-l-4 border-red-400">
            <div class="text-2xl font-bold text-red-600">{{ $totalOverdue }}</div>
            <div class="text-xs text-gray-500 mt-1">Quá hạn</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Theo danh mục --}}
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-tags mr-2 text-gray-400"></i>Theo danh mục</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Danh mục</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Loại tài sản</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Tổng SL</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Còn lại</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($byCategory as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 text-sm text-gray-900 font-medium">{{ $row->category }}</td>
                                <td class="px-4 py-2.5 text-center text-sm text-gray-600">{{ $row->total }}</td>
                                <td class="px-4 py-2.5 text-center text-sm text-gray-600">{{ $row->qty_total }}</td>
                                <td class="px-4 py-2.5 text-center text-sm {{ $row->qty_available == 0 ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                                    {{ $row->qty_available }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Chưa có dữ liệu</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sắp hết hạn bảo hành --}}
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800">
                    <i class="fas fa-shield-alt mr-2 text-orange-400"></i>Sắp hết bảo hành (90 ngày)
                    @if($expiringWarranty->count())
                        <span class="ml-2 px-2 py-0.5 text-xs bg-orange-100 text-orange-700 rounded-full">{{ $expiringWarranty->count() }}</span>
                    @endif
                </h3>
            </div>
            @if($expiringWarranty->isEmpty())
                <div class="p-6 text-center text-gray-400 text-sm">
                    <i class="fas fa-check-circle text-green-400 text-2xl mb-2 block"></i>
                    Không có tài sản nào sắp hết bảo hành
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($expiringWarranty as $asset)
                        <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50">
                            <div>
                                <a href="{{ route('employee-assets.show', $asset) }}" class="text-sm font-medium text-primary hover:underline">
                                    {{ $asset->name }}
                                </a>
                                <div class="text-xs text-gray-400">{{ $asset->asset_code }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium {{ $asset->warranty_expiry->diffInDays(now()) < 30 ? 'text-red-600' : 'text-orange-500' }}">
                                    {{ $asset->warranty_expiry->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    còn {{ now()->diffInDays($asset->warranty_expiry) }} ngày
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Nhân viên đang giữ tài sản --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">
                <i class="fas fa-users mr-2 text-gray-400"></i>Tài sản theo nhân viên
                <span class="ml-2 px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">{{ $byEmployee->count() }} nhân viên</span>
            </h3>
        </div>
        @if($byEmployee->isEmpty())
            <div class="p-8 text-center text-gray-400">Không có nhân viên nào đang giữ tài sản.</div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($byEmployee as $userId => $group)
                    @php $emp = optional($group->first()->employee); @endphp
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="font-medium text-gray-800 text-sm">{{ $emp->name }}</span>
                                <span class="text-xs text-gray-400 ml-2">{{ $emp->department }}</span>
                            </div>
                            <span class="text-xs text-gray-500">{{ $group->count() }} tài sản</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($group as $a)
                                <a href="{{ route('employee-asset-assignments.show', $a) }}"
                                    class="inline-flex items-center px-2.5 py-1 text-xs bg-blue-50 text-blue-700 rounded-full hover:bg-blue-100
                                    {{ $a->status === 'overdue' ? 'bg-red-50 text-red-700' : '' }}">
                                    <i class="{{ $a->asset?->tracking_type === 'serial' ? 'fas fa-barcode' : 'fas fa-cubes' }} mr-1"></i>
                                    {{ optional($a->asset)->name }}
                                    @if($a->quantity > 1) (×{{ $a->quantity }}) @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
