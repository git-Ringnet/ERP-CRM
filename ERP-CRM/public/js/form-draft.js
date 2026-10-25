/**
 * Lưu nháp form trên trình duyệt (localStorage) cho các form khai báo data-draft-key:
 *  - Tự lưu khi đang nhập, nút [data-draft-save] để lưu nháp chủ động.
 *  - Quay lại trang: hỏi khôi phục bản nháp (bỏ qua khi form vừa bị trả về do lỗi - đã có dữ liệu cũ).
 *  - Cảnh báo trước khi rời trang nếu còn nội dung chưa gửi.
 *  - Gửi thành công (lần mở sau không bị trả về lỗi) thì xoá nháp.
 * Không lưu file đính kèm, mật khẩu, token.
 *
 * <form data-draft-key="project-create" data-draft-user="{{ auth()->id() }}" data-draft-has-old="{{ $errors->any() ? 1 : 0 }}">
 */
(function () {
    const SKIP_TYPES = ['file', 'password', 'submit', 'button', 'reset'];
    const SKIP_NAMES = ['_token', '_method'];
    const MAX_AGE_MS = 30 * 24 * 60 * 60 * 1000;

    const storage = {
        get(key) { try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch (e) { return null; } },
        set(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); return true; } catch (e) { return false; } },
        remove(key) { try { localStorage.removeItem(key); } catch (e) { /* ignore */ } },
    };

    const fieldsOf = (form) => Array.from(form.elements).filter((el) =>
        el.name && !el.disabled && !SKIP_TYPES.includes(el.type) && !SKIP_NAMES.includes(el.name) && !el.hasAttribute('data-draft-ignore'));

    function serialize(form) {
        const data = {};
        fieldsOf(form).forEach((el) => {
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (!el.checked) return;
                (data[el.name] = data[el.name] || []).push(el.value);
            } else if (el.tagName === 'SELECT' && el.multiple) {
                data[el.name] = Array.from(el.selectedOptions).map((o) => o.value);
            } else if (el.name.endsWith('[]')) {
                (data[el.name] = data[el.name] || []).push(el.value);
            } else {
                data[el.name] = el.value;
            }
        });
        return data;
    }

    function restore(form, data) {
        const seenArrayIndex = {};
        fieldsOf(form).forEach((el) => {
            if (!(el.name in data)) {
                if (el.type === 'checkbox' || el.type === 'radio') el.checked = false;
                return;
            }
            const value = data[el.name];
            if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = [].concat(value).includes(el.value);
            } else if (el.tagName === 'SELECT' && el.multiple) {
                Array.from(el.options).forEach((o) => { o.selected = [].concat(value).includes(o.value); });
            } else if (Array.isArray(value)) {
                const i = seenArrayIndex[el.name] = (seenArrayIndex[el.name] ?? -1) + 1;
                if (i < value.length) el.value = value[i];
            } else {
                el.value = value;
            }
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    const isEmpty = (data) => Object.values(data).every((v) => (Array.isArray(v) ? v.length === 0 : String(v).trim() === ''));

    function toast(message, icon) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: icon || 'success', title: message });
        }
    }

    function formatTime(ts) {
        const d = new Date(ts);
        const pad = (n) => String(n).padStart(2, '0');
        return `${pad(d.getHours())}:${pad(d.getMinutes())} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
    }

    function showRestoreBanner(form, draft, onRestore, onDiscard) {
        const banner = document.createElement('div');
        banner.className = 'mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900';
        banner.innerHTML = `<span><i class="fas fa-history mr-1"></i> Bạn có bản nháp chưa gửi (lưu lúc ${formatTime(draft.savedAt)}). Khôi phục để nhập tiếp?</span>
            <span class="flex gap-2">
                <button type="button" data-act="restore" class="rounded bg-amber-600 px-3 py-1 font-semibold text-white hover:bg-amber-700">Khôi phục</button>
                <button type="button" data-act="discard" class="rounded border border-amber-400 px-3 py-1 hover:bg-amber-100">Bỏ bản nháp</button>
            </span>`;
        banner.querySelector('[data-act="restore"]').addEventListener('click', () => { onRestore(); banner.remove(); });
        banner.querySelector('[data-act="discard"]').addEventListener('click', () => { onDiscard(); banner.remove(); });
        form.parentNode.insertBefore(banner, form);
    }

    function init(form) {
        const key = `formDraft:${form.dataset.draftKey}:${form.dataset.draftUser || 'guest'}`;
        const hasOld = form.dataset.draftHasOld === '1';
        let initial = JSON.stringify(serialize(form));
        let submitting = false;
        let timer = null;

        const existing = storage.get(key);
        if (existing && hasOld && existing.submittedAt) {
            // Bị trả về do lỗi: giữ nháp, bỏ đánh dấu đã gửi
            delete existing.submittedAt;
            storage.set(key, existing);
        }
        if (existing && (Date.now() - existing.savedAt > MAX_AGE_MS)) {
            storage.remove(key);
        } else if (existing && existing.submittedAt && !hasOld) {
            // Lần gửi trước không bị trả về lỗi -> đã lưu thành công
            storage.remove(key);
        } else if (existing && !hasOld && !isEmpty(existing.fields)) {
            showRestoreBanner(form, existing, () => {
                restore(form, existing.fields);
                toast('Đã khôi phục bản nháp');
            }, () => storage.remove(key));
        }

        const save = (manual) => {
            const fields = serialize(form);
            if (isEmpty(fields)) return;
            const ok = storage.set(key, { savedAt: Date.now(), fields });
            if (manual) toast(ok ? 'Đã lưu nháp trên trình duyệt này' : 'Không lưu được nháp (trình duyệt chặn lưu trữ)', ok ? 'success' : 'error');
        };

        form.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => save(false), 800); });
        form.addEventListener('change', () => { clearTimeout(timer); timer = setTimeout(() => save(false), 300); });

        document.querySelectorAll(`[data-draft-save="${form.id}"]`).forEach((btn) => btn.addEventListener('click', (e) => {
            e.preventDefault();
            save(true);
            initial = JSON.stringify(serialize(form));
        }));

        // Nghe ở window (chạy sau các handler kiểm tra dữ liệu gắn trên form) để biết form có bị chặn gửi không
        window.addEventListener('submit', (e) => {
            if (e.target !== form || e.defaultPrevented) return;
            submitting = true;
            storage.set(key, { fields: serialize(form), savedAt: Date.now(), submittedAt: Date.now() });
        });

        window.addEventListener('beforeunload', (e) => {
            if (submitting || JSON.stringify(serialize(form)) === initial) return;
            save(false);
            e.preventDefault();
            e.returnValue = '';
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form[data-draft-key]').forEach(init);
    });
})();
