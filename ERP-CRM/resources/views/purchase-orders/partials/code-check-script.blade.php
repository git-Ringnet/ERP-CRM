{{--
    Kiểm tra trùng Mã PO khi nhập (trang tạo / cập nhật PO).
    Biến: $ignoreId (id PO đang sửa, null khi tạo mới)
--}}
<script>
(function () {
    const input = document.getElementById('poCodeInput');
    const statusEl = document.getElementById('poCodeStatus');
    if (!input || !statusEl) return;

    const form = input.closest('form');
    const ignoreId = @json($ignoreId ?? null);
    const checkUrl = @json(route('purchase-orders.check-code-api'));
    let timer = null;
    let lastChecked = null;
    let isDuplicate = false;
    let pending = null;

    function setStatus(type, html) {
        const styles = {
            checking: 'text-gray-500',
            ok: 'text-green-600',
            error: 'text-red-600',
        };
        statusEl.className = 'text-xs mt-1 ' + (styles[type] || '');
        statusEl.innerHTML = html;
        input.classList.toggle('border-red-500', type === 'error');
        input.classList.toggle('border-green-500', type === 'ok');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function check() {
        const code = input.value.trim();
        if (code === '') {
            isDuplicate = false;
            lastChecked = '';
            setStatus('error', '<i class="fas fa-exclamation-circle mr-1"></i>Vui lòng nhập Mã PO');
            return Promise.resolve();
        }
        if (code === lastChecked) return pending || Promise.resolve();

        setStatus('checking', '<i class="fas fa-spinner fa-spin mr-1"></i>Đang kiểm tra mã PO...');
        const params = new URLSearchParams({ code });
        if (ignoreId) params.append('ignore_id', ignoreId);

        pending = fetch(checkUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (input.value.trim() !== code) return; // Người dùng đã gõ tiếp
                lastChecked = code;
                isDuplicate = !!data.exists;
                if (data.exists) {
                    const po = data.purchase_order || {};
                    setStatus('error', '<i class="fas fa-times-circle mr-1"></i>Mã PO đã tồn tại'
                        + (po.url ? ` (<a href="${po.url}" target="_blank" class="underline font-semibold">${escapeHtml(po.code)}</a> - ${escapeHtml(po.status_label || '')})` : '')
                        + '. Vui lòng nhập mã khác.');
                } else {
                    setStatus('ok', '<i class="fas fa-check-circle mr-1"></i>Mã PO hợp lệ, chưa được sử dụng');
                }
            })
            .catch(() => {
                // Lỗi mạng: không chặn người dùng, server vẫn kiểm tra trùng khi lưu
                lastChecked = null;
                isDuplicate = false;
                statusEl.innerHTML = '';
            })
            .finally(() => { pending = null; });

        return pending;
    }

    input.addEventListener('input', function () {
        isDuplicate = false;
        clearTimeout(timer);
        timer = setTimeout(check, 400);
    });
    input.addEventListener('blur', check);
    // Cho phép script khác (VD: tự sinh mã theo NCC) yêu cầu kiểm tra lại
    input.addEventListener('po-code:changed', () => { lastChecked = null; check(); });

    if (form) {
        form.addEventListener('submit', function (e) {
            if (input.value.trim() !== lastChecked) {
                // Chưa kiểm tra mã mới nhất: kiểm tra xong rồi mới gửi
                e.preventDefault();
                e.stopImmediatePropagation();
                // Giữ đúng nút đã bấm (VD: "Gửi duyệt" có name="submit_approval")
                const submitter = e.submitter || null;
                check().then(() => {
                    if (!isDuplicate && input.value.trim() !== '') {
                        if (form.requestSubmit) {
                            form.requestSubmit(submitter);
                        } else {
                            if (submitter && submitter.name) {
                                const hidden = document.createElement('input');
                                hidden.type = 'hidden';
                                hidden.name = submitter.name;
                                hidden.value = submitter.value;
                                form.appendChild(hidden);
                            }
                            form.submit();
                        }
                    } else {
                        input.focus();
                    }
                });
                return;
            }
            if (isDuplicate) {
                e.preventDefault();
                e.stopImmediatePropagation();
                input.focus();
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Mã PO đã tồn tại', text: 'Vui lòng nhập Mã PO khác trước khi lưu.' });
                }
            }
        }, true);
    }

    if (input.value.trim() !== '') check();
})();
</script>
