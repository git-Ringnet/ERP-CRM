/**
 * Ô chọn nhiều file (input[type=file][multiple][data-accumulate-files]): mỗi lần chọn thêm sẽ CỘNG DỒN
 * vào danh sách đã chọn thay vì thay thế (mặc định của trình duyệt), hiện danh sách kèm nút bỏ từng file.
 * Không cần lưu nháp mới đính kèm thêm được.
 */
(function () {
    const fileKey = (f) => `${f.name}|${f.size}|${f.lastModified}`;
    const formatSize = (bytes) => (bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB');

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function init(input) {
        if (input.dataset.accumulateReady || typeof DataTransfer === 'undefined') return;
        input.dataset.accumulateReady = '1';

        let selected = [];
        const list = document.createElement('ul');
        list.className = 'mt-1 space-y-1 text-xs';
        input.insertAdjacentElement('afterend', list);

        const sync = () => {
            const dt = new DataTransfer();
            selected.forEach((f) => dt.items.add(f));
            input.files = dt.files;
            list.innerHTML = selected.map((f, i) => `
                <li class="flex items-center justify-between gap-2 rounded border border-gray-200 bg-gray-50 px-2 py-1">
                    <span class="truncate text-gray-700"><i class="fas fa-paperclip mr-1 text-gray-400"></i>${escapeHtml(f.name)} <span class="text-gray-400">(${formatSize(f.size)})</span></span>
                    <button type="button" data-remove="${i}" class="text-red-500 hover:text-red-700" title="Bỏ file này"><i class="fas fa-times"></i></button>
                </li>`).join('');
        };

        input.addEventListener('change', () => {
            const known = new Set(selected.map(fileKey));
            Array.from(input.files).forEach((f) => {
                if (!known.has(fileKey(f))) {
                    selected.push(f);
                    known.add(fileKey(f));
                }
            });
            sync();
        });

        list.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove]');
            if (!btn) return;
            selected.splice(Number(btn.dataset.remove), 1);
            sync();
        });

        input.form?.addEventListener('reset', () => { selected = []; setTimeout(sync, 0); });
    }

    const SELECTOR = 'input[type="file"][multiple][data-accumulate-files]';
    const scan = (root) => {
        if (root.matches?.(SELECTOR)) init(root);
        root.querySelectorAll?.(SELECTOR).forEach(init);
    };

    document.addEventListener('DOMContentLoaded', () => {
        scan(document);
        // Ô file trong modal / phần render động
        new MutationObserver((mutations) => mutations.forEach((m) => m.addedNodes.forEach((n) => n.nodeType === 1 && scan(n))))
            .observe(document.body, { childList: true, subtree: true });
    });
})();
