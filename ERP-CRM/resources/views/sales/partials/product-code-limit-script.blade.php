{{-- Báo ngay khi mã sản phẩm vượt giới hạn, không để gửi form rồi mới bị trả lỗi --}}
<script>
window.productCodeLimit = (() => {
    const MAX = @json(\App\Models\Product::CODE_MAX_LENGTH);
    const INPUTS = '.product-searchable .searchable-input, .bom-product-searchable .searchable-input';
    const message = length => `Mã sản phẩm dài ${length} ký tự, tối đa ${MAX} ký tự. Vui lòng rút gọn.`;

    const render = (input, length) => {
        const tooLong = length > MAX;
        let note = input.parentElement.querySelector('.product-code-limit-error');
        input.classList.toggle('border-red-500', tooLong);

        if (!tooLong) {
            note?.remove();
            return;
        }
        if (!note) {
            note = document.createElement('p');
            note.className = 'product-code-limit-error mt-1 text-xs font-medium text-red-600';
            input.insertAdjacentElement('afterend', note);
        }
        note.textContent = message(length);
    };

    // Trả về nội dung lỗi của dòng sản phẩm, null nếu hợp lệ
    const check = row => {
        const length = (row.querySelector('.new-code-input')?.value || '').trim().length;
        if (length <= MAX) {
            return null;
        }
        const input = row.querySelector('.searchable-input');
        if (input) {
            render(input, length);
        }
        return message(length);
    };

    document.addEventListener('input', event => {
        if (event.target.matches(INPUTS)) {
            render(event.target, event.target.value.trim().length);
        }
    });

    // Chọn sản phẩm từ danh sách gợi ý đổi nội dung ô mà không phát sinh sự kiện input
    document.addEventListener('click', () => setTimeout(() => {
        document.querySelectorAll('.product-code-limit-error').forEach(note => {
            const input = note.parentElement.querySelector('.searchable-input');
            if (input) {
                render(input, input.value.trim().length);
            }
        });
    }));

    // Form BOM không có bước kiểm tra riêng trước khi gửi
    document.addEventListener('submit', event => {
        const error = [...event.target.querySelectorAll('.bom-edit-row')].map(check).find(Boolean);
        if (error) {
            event.preventDefault();
            alert(error);
        }
    });

    return { check };
})();
</script>
