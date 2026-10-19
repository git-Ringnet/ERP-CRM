{{-- Gợi ý tên NPP từ danh mục Nhà cung cấp; tên mới khi lưu sẽ được tự tạo thành Nhà cung cấp --}}
@once
    <datalist id="other-distributor-options">
        @foreach(\App\Models\Supplier::orderBy('name')->pluck('name') as $supplierName)
            <option value="{{ $supplierName }}"></option>
        @endforeach
    </datalist>
    <p class="mt-1 text-[11px] text-gray-500">Chọn NPP có sẵn trong danh sách gợi ý; NPP chưa có sẽ được tự tạo vào danh mục Nhà cung cấp khi lưu.</p>
@endonce
