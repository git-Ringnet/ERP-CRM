{{--
    Hiển thị Mô tả sản phẩm và nguồn dữ liệu dưới ô chọn sản phẩm (form tạo/sửa đơn).
    Biến: $isNew (bool), $description (?string), $name (?string)
    Markup phải khớp với renderProductSourceHint() trong product-source-hint-script.
--}}
@php
    $description = trim((string) ($description ?? ''));
@endphp
<div class="product-desc-source mt-1 text-[11px] leading-snug">
    @if($isNew ?? false)
        <span class="inline-flex items-center gap-1 rounded bg-gray-100 border border-gray-200 px-1.5 py-0.5 font-semibold text-gray-600">
            <i class="fas fa-keyboard text-[9px]"></i> Mã mới – chưa có trong danh mục (mô tả nhập tay / từ BOM)
        </span>
    @elseif($description !== '')
        <div class="text-gray-600"><span class="font-semibold text-gray-700">Mô tả:</span> {{ $description }}</div>
        <span class="mt-0.5 inline-flex items-center gap-1 rounded bg-blue-50 border border-blue-100 px-1.5 py-0.5 font-semibold text-blue-700">
            <i class="fas fa-database text-[9px]"></i> Lấy từ danh mục sản phẩm
        </span>
    @else
        <span class="inline-flex items-center gap-1 rounded bg-amber-50 border border-amber-200 px-1.5 py-0.5 font-semibold text-amber-700">
            <i class="fas fa-database text-[9px]"></i> Danh mục sản phẩm chưa có Mô tả
        </span>
    @endif
</div>
