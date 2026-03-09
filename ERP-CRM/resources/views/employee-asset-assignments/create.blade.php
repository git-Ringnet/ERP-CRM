@extends('layouts.app')

@section('title', 'Cấp phát tài sản')
@section('page-title', 'Cấp phát Tài sản cho Nhân viên')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex items-center gap-3">
            <a href="{{ route('employee-asset-assignments.index') }}" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="font-semibold text-gray-800">Tạo phiếu cấp phát tài sản</h2>
        </div>

        <form action="{{ route('employee-asset-assignments.store') }}" method="POST" class="p-6 space-y-5">
            @csrf

            {{-- Chọn tài sản --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tài sản <span class="text-red-500">*</span></label>
                <select name="employee_asset_id" id="asset-select" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('employee_asset_id') border-red-500 @enderror"
                    onchange="updateAssetInfo(this)">
                    <option value="">— Chọn tài sản —</option>
                    @foreach($availableAssets as $asset)
                        <option value="{{ $asset->id }}"
                            data-type="{{ $asset->tracking_type }}"
                            data-available="{{ $asset->quantity_available }}"
                            data-serial="{{ $asset->serial_number }}"
                            {{ (old('employee_asset_id', optional($selectedAsset)->id) == $asset->id) ? 'selected' : '' }}>
                            [{{ $asset->asset_code }}] {{ $asset->name }}
                            ({{ $asset->tracking_type === 'serial' ? 'Serial: ' . ($asset->serial_number ?? 'N/A') : 'Còn: ' . $asset->quantity_available }})
                        </option>
                    @endforeach
                </select>
                @error('employee_asset_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <div id="asset-info" class="hidden mt-2 p-3 bg-blue-50 rounded-lg text-sm text-blue-700"></div>
            </div>

            {{-- Số lượng (chỉ hiện nếu quantity-type) --}}
            <div id="qty-field" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng cấp <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" id="quantity-input"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <p id="qty-hint" class="text-xs text-gray-400 mt-1"></p>
                @error('quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Chọn nhân viên --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên nhận <span class="text-red-500">*</span></label>
                <select name="user_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('user_id') border-red-500 @enderror">
                    <option value="">— Chọn nhân viên —</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ old('user_id') == $emp->id ? 'selected' : '' }}>
                            [{{ $emp->employee_code }}] {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
                @error('user_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày cấp phát <span class="text-red-500">*</span></label>
                    <input type="date" name="assigned_date" value="{{ old('assigned_date', date('Y-m-d')) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dự kiến hoàn trả</label>
                    <input type="date" name="expected_return_date" value="{{ old('expected_return_date') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tình trạng khi giao <span class="text-red-500">*</span></label>
                <select name="condition_at_assignment" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="new"  {{ old('condition_at_assignment') == 'new'  ? 'selected' : '' }}>Mới</option>
                    <option value="good" {{ old('condition_at_assignment', 'good') == 'good' ? 'selected' : '' }}>Tốt</option>
                    <option value="fair" {{ old('condition_at_assignment') == 'fair' ? 'selected' : '' }}>Khá</option>
                    <option value="poor" {{ old('condition_at_assignment') == 'poor' ? 'selected' : '' }}>Kém</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lý do / Mục đích cấp phát</label>
                <textarea name="reason" rows="2" placeholder="VD: Phục vụ công việc dự án X, đi công tác..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('reason') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium">
                    <i class="fas fa-paper-plane mr-2"></i>Xác nhận cấp phát
                </button>
                <a href="{{ route('employee-asset-assignments.index') }}"
                    class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">Huỷ</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function updateAssetInfo(select) {
    const opt = select.options[select.selectedIndex];
    const type = opt.dataset.type;
    const available = parseInt(opt.dataset.available || 1);
    const info = document.getElementById('asset-info');
    const qtyField = document.getElementById('qty-field');
    const qtyHint = document.getElementById('qty-hint');
    const qtyInput = document.getElementById('quantity-input');

    if (!opt.value) {
        info.classList.add('hidden');
        qtyField.classList.add('hidden');
        return;
    }
    info.classList.remove('hidden');

    if (type === 'serial') {
        info.textContent = `Serial: ${opt.dataset.serial || 'N/A'} — Sẵn sàng cấp phát`;
        qtyField.classList.add('hidden');
    } else {
        info.textContent = `Còn sẵn: ${available} đơn vị`;
        qtyField.classList.remove('hidden');
        qtyInput.max = available;
        qtyHint.textContent = `Tối đa: ${available}`;
    }
}
// Init
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('asset-select');
    if (sel.value) updateAssetInfo(sel);
});
</script>
@endpush
@endsection
