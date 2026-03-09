@extends('layouts.app')

@section('title', 'Chi tiết cấp phát')
@section('page-title', 'Chi tiết Phiếu Cấp phát')

@section('content')
<div class="w-full space-y-4">

    {{-- Header card --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('employee-asset-assignments.index') }}" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <div class="text-xs text-gray-400">Phiếu #{{ $employeeAssetAssignment->id }}</div>
                    <h2 class="font-semibold text-gray-800">Cấp phát: {{ optional($employeeAssetAssignment->asset)->name }}</h2>
                </div>
            </div>
            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-{{ $employeeAssetAssignment->status_color }}-100 text-{{ $employeeAssetAssignment->status_color }}-700">
                {{ $employeeAssetAssignment->status_label }}
            </span>
        </div>

        <div class="p-5 space-y-4">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                <div>
                    <dt class="text-xs text-gray-500 uppercase">Tài sản</dt>
                    <dd class="text-sm font-medium text-gray-900 mt-0.5">
                        <a href="{{ route('employee-assets.show', $employeeAssetAssignment->asset) }}" class="text-primary hover:underline">
                            {{ optional($employeeAssetAssignment->asset)->name }}
                        </a>
                        <span class="text-gray-400 text-xs ml-1">[{{ optional($employeeAssetAssignment->asset)->asset_code }}]</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase">Nhân viên nhận</dt>
                    <dd class="text-sm font-medium text-gray-900 mt-0.5">{{ optional($employeeAssetAssignment->employee)->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase">Số lượng cấp</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $employeeAssetAssignment->quantity }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase">Người cấp phát</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ optional($employeeAssetAssignment->assignedByUser)->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase">Ngày cấp</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $employeeAssetAssignment->assigned_date->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase">Dự kiến hoàn trả</dt>
                    <dd class="text-sm mt-0.5 {{ $employeeAssetAssignment->status === 'overdue' ? 'text-red-600 font-medium' : 'text-gray-800' }}">
                        {{ $employeeAssetAssignment->expected_return_date?->format('d/m/Y') ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase">Tình trạng khi giao</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $employeeAssetAssignment->condition_label }}</dd>
                </div>
                @if($employeeAssetAssignment->returned_date)
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Ngày thu hồi</dt>
                        <dd class="text-sm text-gray-800 mt-0.5">{{ $employeeAssetAssignment->returned_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Tình trạng khi nhận lại</dt>
                        <dd class="text-sm text-gray-800 mt-0.5">{{ $employeeAssetAssignment->condition_return_label }}</dd>
                    </div>
                @endif
            </dl>

            @if($employeeAssetAssignment->reason)
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">Lý do cấp phát</p>
                    <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3">{{ $employeeAssetAssignment->reason }}</p>
                </div>
            @endif

            @if($employeeAssetAssignment->return_note)
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">Ghi chú thu hồi</p>
                    <p class="text-sm text-gray-700 bg-orange-50 rounded-lg p-3">{{ $employeeAssetAssignment->return_note }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Thu hồi form (chỉ hiện khi đang active/overdue) --}}
    @if($employeeAssetAssignment->returned_date === null)
        <div class="bg-white rounded-lg shadow-sm border border-orange-200">
            <div class="p-4 border-b border-orange-100 bg-orange-50 rounded-t-lg">
                <h3 class="font-semibold text-orange-800"><i class="fas fa-undo mr-2"></i>Thu hồi tài sản</h3>
            </div>
            <form action="{{ route('employee-asset-assignments.return', $employeeAssetAssignment) }}" method="POST" class="p-5 space-y-4">
                @csrf

                @if(session('error'))
                    <div class="p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày thu hồi <span class="text-red-500">*</span></label>
                        <input type="date" name="returned_date" value="{{ date('Y-m-d') }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tình trạng khi nhận <span class="text-red-500">*</span></label>
                        <select name="condition_at_return" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="new">Mới</option>
                            <option value="good" selected>Tốt</option>
                            <option value="fair">Khá</option>
                            <option value="poor">Kém</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (hư hỏng, bảo trì...)</label>
                    <textarea name="return_note" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Ghi chú tình trạng thiết bị khi thu hồi..."></textarea>
                </div>
                <button type="submit"
                    class="px-5 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm font-medium">
                    <i class="fas fa-undo mr-2"></i>Xác nhận thu hồi
                </button>
            </form>
        </div>
    @else
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
            <i class="fas fa-check-circle mr-2"></i>
            Tài sản đã được thu hồi vào <strong>{{ $employeeAssetAssignment->returned_date->format('d/m/Y') }}</strong>.
        </div>
    @endif
</div>
@endsection
