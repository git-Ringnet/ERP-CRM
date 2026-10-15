    /**
     * Hiển thị Mô tả sản phẩm + nhãn nguồn dữ liệu dưới ô chọn sản phẩm.
     * Markup khớp với partial sales.partials.product-source-hint.
     * info: { isNew: bool, description: string }
     */
    function renderProductSourceHint(row, info) {
        if (!row) return;
        // Form tạo/sửa đơn dùng .product-name-col; bảng BOM dùng ô đầu tiên của dòng
        const col = row.querySelector('.product-name-col') || row.querySelector('td');
        if (!col) return;

        let hint = col.querySelector('.product-desc-source');
        if (!hint) {
            hint = document.createElement('div');
            hint.className = 'product-desc-source mt-1 text-[11px] leading-snug';
            col.appendChild(hint);
        }

        const escape = (s) => {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        };
        const description = ((info && info.description) || '').trim();

        if (!info) {
            hint.innerHTML = '';
        } else if (info.isNew) {
            hint.innerHTML = '<span class="inline-flex items-center gap-1 rounded bg-gray-100 border border-gray-200 px-1.5 py-0.5 font-semibold text-gray-600">'
                + '<i class="fas fa-keyboard text-[9px]"></i> Mã mới – chưa có trong danh mục (mô tả nhập tay / từ BOM)</span>';
        } else if (description) {
            hint.innerHTML = '<div class="text-gray-600"><span class="font-semibold text-gray-700">Mô tả:</span> ' + escape(description) + '</div>'
                + '<span class="mt-0.5 inline-flex items-center gap-1 rounded bg-blue-50 border border-blue-100 px-1.5 py-0.5 font-semibold text-blue-700">'
                + '<i class="fas fa-database text-[9px]"></i> Lấy từ danh mục sản phẩm</span>';
        } else {
            hint.innerHTML = '<span class="inline-flex items-center gap-1 rounded bg-amber-50 border border-amber-200 px-1.5 py-0.5 font-semibold text-amber-700">'
                + '<i class="fas fa-database text-[9px]"></i> Danh mục sản phẩm chưa có Mô tả</span>';
        }
    }
