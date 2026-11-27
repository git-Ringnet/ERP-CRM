@once
@push('modals')
{{-- Modal xác nhận xóa sự kiện marketing --}}
<div id="mktDeleteModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="mktDeleteTitle">
    <div class="absolute inset-0 bg-gray-900/60" data-mkt-delete-close></div>

    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-start gap-3 px-6 pt-6">
            <div id="mktDeleteIcon" class="shrink-0 w-11 h-11 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                <i class="fas fa-trash-alt"></i>
            </div>
            <div class="min-w-0">
                <h3 id="mktDeleteTitle" class="text-lg font-bold text-gray-900">Xóa sự kiện</h3>
                <p id="mktDeleteSubtitle" class="text-sm text-gray-500 mt-0.5"></p>
            </div>
        </div>

        <div class="px-6 py-4 space-y-4">
            {{-- Xóa thông thường --}}
            <p id="mktDeleteSimple" class="text-sm text-gray-600">
                Bạn có chắc chắn muốn xóa sự kiện này? Hành động này không thể hoàn tác.
            </p>

            {{-- Xóa sự kiện đã duyệt / đang chờ duyệt / đã hoàn thành --}}
            <div id="mktDeleteStrong" class="space-y-4 hidden">
                <div class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm text-red-800">
                    <p class="font-semibold mb-1.5"><i class="fas fa-exclamation-triangle mr-1"></i>Dữ liệu sau sẽ bị xóa vĩnh viễn:</p>
                    <ul class="list-disc pl-5 space-y-0.5">
                        <li>Toàn bộ ticket và yêu cầu marketing (kể cả yêu cầu thanh toán)</li>
                        <li>Giao dịch nhà cung cấp / công nợ của sự kiện</li>
                        <li>Danh sách khách mời</li>
                    </ul>
                    <p class="mt-2 text-red-700">Dự án và giao dịch vật phẩm liên quan sẽ bị gỡ liên kết với sự kiện.</p>
                </div>

                <div>
                    <label for="mktDeleteCodeInput" class="block text-sm text-gray-700 mb-1.5">
                        Nhập mã sự kiện <span id="mktDeleteCode" class="font-mono font-bold text-gray-900 bg-gray-100 px-1.5 py-0.5 rounded"></span> để xác nhận
                    </label>
                    <input id="mktDeleteCodeInput" type="text" autocomplete="off" spellcheck="false"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" data-mkt-delete-close
                class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                Hủy
            </button>
            <button type="button" id="mktDeleteConfirmBtn"
                class="px-4 py-2 rounded-lg bg-red-600 text-sm font-semibold text-white hover:bg-red-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                <i class="fas fa-trash-alt mr-1"></i>Xóa sự kiện
            </button>
        </div>
    </div>
</div>

{{-- Modal xác nhận xóa quỹ hãng --}}
<div id="mktFundDeleteModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-gray-900/60" data-fund-delete-close></div>

    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-start gap-3 px-6 pt-6">
            <div class="shrink-0 w-11 h-11 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="min-w-0">
                <h3 class="text-lg font-bold text-gray-900">Xóa quỹ hãng</h3>
                <p class="text-sm text-gray-500 mt-0.5">Dùng khi khai báo quỹ bị sai.</p>
            </div>
        </div>

        <div class="px-6 py-4 space-y-3">
            <dl class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 text-sm grid grid-cols-3 gap-y-1.5">
                <dt class="text-gray-500">Hãng</dt><dd id="mktFundDelSupplier" class="col-span-2 font-semibold text-gray-900"></dd>
                <dt class="text-gray-500">Tên quỹ</dt><dd id="mktFundDelName" class="col-span-2 font-semibold text-gray-900 break-words"></dd>
                <dt class="text-gray-500">Thời gian</dt><dd id="mktFundDelPeriod" class="col-span-2 text-gray-900"></dd>
                <dt class="text-gray-500">Tổng tiền</dt><dd id="mktFundDelAmount" class="col-span-2 font-semibold text-gray-900"></dd>
            </dl>
            <p class="text-sm text-gray-600">
                Quỹ và lịch sử nạp/điều chỉnh của quỹ sẽ bị xóa vĩnh viễn. Hành động này không thể hoàn tác.
            </p>
        </div>

        <div class="flex justify-end gap-2 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" data-fund-delete-close
                class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                Hủy
            </button>
            <button type="button" id="mktFundDeleteConfirmBtn"
                class="px-4 py-2 rounded-lg bg-red-600 text-sm font-semibold text-white hover:bg-red-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                <i class="fas fa-trash-alt mr-1"></i>Xóa quỹ
            </button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    /**
     * Mở modal xác nhận xóa sự kiện marketing.
     * - Nháp / Từ chối / Đã hủy: xác nhận thông thường.
     * - Chờ duyệt / Đã duyệt / Đã hoàn thành (chỉ admin thấy nút): cảnh báo mất dữ liệu + nhập đúng mã sự kiện.
     * Luôn trả về false để chặn submit mặc định; form được submit khi bấm "Xóa sự kiện" trong modal.
     */
    (function () {
        let pendingForm = null;
        let requiredCode = '';

        const el = (id) => document.getElementById(id);

        function closeModal() {
            const modal = el('mktDeleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            pendingForm = null;
        }

        function syncConfirmState() {
            if (!requiredCode) return;
            const ok = el('mktDeleteCodeInput').value.trim() === requiredCode;
            el('mktDeleteConfirmBtn').disabled = !ok;
        }

        window.confirmDeleteMarketingEvent = function (btn) {
            const modal = el('mktDeleteModal');
            if (!modal) return confirm('Bạn có chắc chắn muốn xóa sự kiện này?');

            const status = btn.dataset.status;
            const code = (btn.dataset.code || '').trim();
            const statusLabel = btn.dataset.statusLabel || status;
            const strong = ['pending', 'approved', 'completed'].includes(status);

            pendingForm = btn.closest('form');
            requiredCode = strong ? code : '';

            el('mktDeleteSubtitle').textContent = code + ' · Trạng thái: ' + statusLabel;
            el('mktDeleteSimple').classList.toggle('hidden', strong);
            el('mktDeleteStrong').classList.toggle('hidden', !strong);
            el('mktDeleteCode').textContent = code;
            el('mktDeleteCodeInput').value = '';
            el('mktDeleteConfirmBtn').disabled = strong;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => (strong ? el('mktDeleteCodeInput') : el('mktDeleteConfirmBtn')).focus(), 50);

            return false;
        };

        document.addEventListener('DOMContentLoaded', function () {
            const modal = el('mktDeleteModal');
            if (!modal) return;

            modal.querySelectorAll('[data-mkt-delete-close]').forEach((n) => n.addEventListener('click', closeModal));
            el('mktDeleteCodeInput').addEventListener('input', syncConfirmState);
            el('mktDeleteCodeInput').addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (!el('mktDeleteConfirmBtn').disabled) el('mktDeleteConfirmBtn').click();
                }
            });
            el('mktDeleteConfirmBtn').addEventListener('click', function () {
                if (!pendingForm || this.disabled) return;
                const form = pendingForm;
                this.disabled = true;
                form.submit();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        });
    })();

    /**
     * Modal xác nhận xóa quỹ hãng.
     */
    (function () {
        let pendingForm = null;
        const el = (id) => document.getElementById(id);

        function closeModal() {
            const modal = el('mktFundDeleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            pendingForm = null;
        }

        window.confirmDeleteFund = function (btn) {
            const modal = el('mktFundDeleteModal');
            if (!modal) return confirm('Xóa quỹ hãng này?');

            pendingForm = btn.closest('form');
            el('mktFundDelSupplier').textContent = btn.dataset.fundSupplier || '—';
            el('mktFundDelName').textContent = btn.dataset.fundName || '—';
            el('mktFundDelPeriod').textContent = btn.dataset.fundPeriod || '—';
            el('mktFundDelAmount').textContent = btn.dataset.fundAmount || '—';
            el('mktFundDeleteConfirmBtn').disabled = false;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => el('mktFundDeleteConfirmBtn').focus(), 50);
            return false;
        };

        document.addEventListener('DOMContentLoaded', function () {
            const modal = el('mktFundDeleteModal');
            if (!modal) return;

            modal.querySelectorAll('[data-fund-delete-close]').forEach((n) => n.addEventListener('click', closeModal));
            el('mktFundDeleteConfirmBtn').addEventListener('click', function () {
                if (!pendingForm || this.disabled) return;
                this.disabled = true;
                pendingForm.submit();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        });
    })();
</script>
@endpush
@endonce
