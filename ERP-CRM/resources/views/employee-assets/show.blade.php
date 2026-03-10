@extends('layouts.app')

@section('title', $employeeAsset->name)
@section('page-title', 'Chi tiết tài sản')

@section('content')
<div class="w-full space-y-4">

    {{-- Header card --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('employee-assets.index') }}" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <div class="font-mono text-xs text-gray-400">{{ $employeeAsset->asset_code }}</div>
                    <h2 class="font-semibold text-gray-800 text-lg">{{ $employeeAsset->name }}</h2>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-{{ $employeeAsset->status_color }}-100 text-{{ $employeeAsset->status_color }}-700">
                    {{ $employeeAsset->status_label }}
                </span>
                <a href="{{ route('employee-assets.edit', $employeeAsset) }}"
                    class="px-3 py-1.5 text-sm bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
                @if($employeeAsset->status === 'available' && !$employeeAsset->activeAssignments->count())
                    <a href="{{ route('employee-asset-assignments.create', ['asset_id' => $employeeAsset->id]) }}"
                        class="px-3 py-1.5 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">
                        <i class="fas fa-user-plus mr-1"></i>Cấp phát
                    </a>
                @endif
            </div>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Thông tin tổng quát --}}
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Thông tin chung</h3>
                <dl class="space-y-2">
                    <div class="flex">
                        <dt class="w-36 text-sm text-gray-500">Danh mục:</dt>
                        <dd class="text-sm text-gray-800 font-medium">{{ $employeeAsset->category }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-36 text-sm text-gray-500">Loại theo dõi:</dt>
                        <dd>
                            @if($employeeAsset->tracking_type === 'serial')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-violet-100 text-violet-700">
                                    <i class="fas fa-barcode mr-1"></i>Theo mã/serial
                                </span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-sky-100 text-sky-700">
                                    <i class="fas fa-cubes mr-1"></i>Theo số lượng
                                </span>
                            @endif
                        </dd>
                    </div>
                    @if($employeeAsset->serial_number)
                        <div class="flex">
                            <dt class="w-36 text-sm text-gray-500">Serial:</dt>
                            <dd class="text-sm font-mono text-gray-800">{{ $employeeAsset->serial_number }}</dd>
                        </div>
                    @endif
                    @if($employeeAsset->tracking_type === 'quantity')
                        <div class="flex">
                            <dt class="w-36 text-sm text-gray-500">Số lượng:</dt>
                            <dd class="text-sm text-gray-800">
                                <span class="{{ $employeeAsset->quantity_available == 0 ? 'text-red-600 font-bold' : '' }}">
                                    {{ $employeeAsset->quantity_available }}
                                </span> / {{ $employeeAsset->quantity_total }} (còn / tổng)
                            </dd>
                        </div>
                    @endif
                    <div class="flex">
                        <dt class="w-36 text-sm text-gray-500">Hãng:</dt>
                        <dd class="text-sm text-gray-800">{{ $employeeAsset->brand ?? '—' }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-36 text-sm text-gray-500">Vị trí:</dt>
                        <dd class="text-sm text-gray-800">{{ $employeeAsset->location ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Thông tin mua & bảo hành --}}
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Mua & Bảo hành</h3>
                <dl class="space-y-2">
                    <div class="flex">
                        <dt class="w-36 text-sm text-gray-500">Ngày mua:</dt>
                        <dd class="text-sm text-gray-800">{{ $employeeAsset->purchase_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-36 text-sm text-gray-500">Giá mua:</dt>
                        <dd class="text-sm text-gray-800">
                            {{ $employeeAsset->purchase_price ? number_format($employeeAsset->purchase_price, 0) . ' ₫' : '—' }}
                        </dd>
                    </div>
                    <div class="flex items-center">
                        <dt class="w-36 text-sm text-gray-500">Bảo hành đến:</dt>
                        <dd class="text-sm">
                            @if($employeeAsset->warranty_expiry)
                                @php $expired = $employeeAsset->warranty_expiry->isPast(); @endphp
                                <span class="{{ $expired ? 'text-red-600 font-medium' : 'text-gray-800' }}">
                                    {{ $employeeAsset->warranty_expiry->format('d/m/Y') }}
                                    @if($expired) <span class="text-xs">(đã hết hạn)</span>
                                    @elseif($employeeAsset->warranty_expiry->diffInDays(now()) < 90)
                                        <span class="text-orange-500 text-xs">(sắp hết hạn)</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </dd>
                    </div>
                </dl>
                @if($employeeAsset->description)
                    <div class="mt-3">
                        <p class="text-sm text-gray-500 mb-1">Ghi chú:</p>
                        <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3">{{ $employeeAsset->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Ảnh --}}
        @if($employeeAsset->image)
            <div class="px-5 pb-5">
                <img src="{{ Storage::url($employeeAsset->image) }}" alt="{{ $employeeAsset->name }}"
                    class="h-40 w-40 object-cover rounded-xl border border-gray-200">
            </div>
        @endif
    </div>

    {{-- Lịch sử cấp phát --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">
                <i class="fas fa-history mr-2 text-gray-400"></i>Lịch sử cấp phát
                <span class="ml-2 px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">{{ $employeeAsset->assignments->count() }}</span>
            </h3>
            @if($employeeAsset->status === 'available')
                <a href="{{ route('employee-asset-assignments.create', ['asset_id' => $employeeAsset->id]) }}"
                    class="inline-flex items-center px-3 py-1.5 text-xs bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <i class="fas fa-plus mr-1"></i>Cấp phát
                </a>
            @endif
        </div>

        @if($employeeAsset->assignments->isEmpty())
            <div class="p-8 text-center text-gray-400">
                <i class="fas fa-users text-3xl mb-2 block"></i>
                Chưa có lịch sử cấp phát
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhân viên</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày cấp</th>
                            @if($employeeAsset->tracking_type === 'quantity')
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người cấp</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dự kiến trả</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày trả</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($employeeAsset->assignments as $a)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ optional($a->employee)->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $a->assigned_date->format('d/m/Y') }}</td>
                                @if($employeeAsset->tracking_type === 'quantity')
                                    <td class="px-4 py-3 text-sm text-center text-gray-600">{{ $a->quantity }}</td>
                                @endif
                                <td class="px-4 py-3 text-sm text-gray-500">{{ optional($a->assignedByUser)->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $a->expected_return_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $a->returned_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $a->status_color }}-100 text-{{ $a->status_color }}-700">
                                        {{ $a->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('employee-asset-assignments.show', $a) }}"
                                        class="text-blue-600 hover:text-blue-800 text-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
