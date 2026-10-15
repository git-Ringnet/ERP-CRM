    // Nhập kho: chỉ tạo sản phẩm mới khi KHÔNG tìm thấy trong danh mục; server kiểm tra trùng mã
    const CAN_QUICK_CREATE_PRODUCT = @json(auth()->user()?->can('create', \App\Models\Product::class) ?? false);
    const PRODUCT_QUICK_STORE_URL = @json(route('products.quick-store'));

    function appendQuickCreateProductOption(dropdown, query, input, hiddenInput, onSelectCallback) {
        if (!CAN_QUICK_CREATE_PRODUCT || !query) return;
        const opt = document.createElement('div');
        opt.className = 'searchable-option text-emerald-700 font-semibold border-t border-gray-100';
        opt.innerHTML = '<i class="fas fa-plus-circle mr-1"></i> Tạo sản phẩm mới: "' + query.replace(/</g, '&lt;') + '"';
        opt.addEventListener('click', () => {
            dropdown.style.display = 'none';
            quickCreateImportProduct(query, input, hiddenInput, onSelectCallback);
        });
        dropdown.appendChild(opt);
    }

    function selectImportProduct(p, input, hiddenInput, onSelectCallback) {
        input.value = p.code;
        hiddenInput.value = p.id;
        hiddenInput.dataset.unit = p.unit || 'Cái';
        const fakeOpt = document.createElement('div');
        Object.assign(fakeOpt.dataset, { value: p.id, code: p.code, name: p.name, text: p.code + ' - ' + p.name, unit: p.unit || 'Cái', cost: p.cost || '0', warranty: p.warranty_months || '' });
        if (onSelectCallback) onSelectCallback(p.id, fakeOpt);
    }

    async function quickCreateImportProduct(query, input, hiddenInput, onSelectCallback) {
        const esc = (s) => String(s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const result = await Swal.fire({
            title: 'Tạo sản phẩm mới',
            html: '<p class="text-xs text-gray-500 mb-2">Chỉ tạo khi không tìm thấy trong danh mục. Hệ thống sẽ kiểm tra trùng mã khi lưu.</p>'
                + '<input id="qcp_code" class="swal2-input" placeholder="Mã sản phẩm *" value="' + esc(query.toUpperCase()) + '">'
                + '<input id="qcp_name" class="swal2-input" placeholder="Tên sản phẩm *">'
                + '<input id="qcp_unit" class="swal2-input" placeholder="Đơn vị (mặc định: Cái)">',
            showCancelButton: true,
            confirmButtonText: 'Lưu sản phẩm',
            cancelButtonText: 'Hủy',
            preConfirm: async () => {
                const payload = {
                    code: document.getElementById('qcp_code').value.trim(),
                    name: document.getElementById('qcp_name').value.trim(),
                    unit: document.getElementById('qcp_unit').value.trim(),
                };
                if (!payload.code || !payload.name) {
                    Swal.showValidationMessage('Vui lòng nhập Mã và Tên sản phẩm');
                    return false;
                }
                const res = await fetch(PRODUCT_QUICK_STORE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    if (data.existing) {
                        return { existing: data.existing, message: data.message };
                    }
                    Swal.showValidationMessage(data.message || 'Không tạo được sản phẩm');
                    return false;
                }
                return { product: data.product };
            },
        });
        if (!result.isConfirmed || !result.value) return;

        if (result.value.existing) {
            const use = await Swal.fire({ icon: 'warning', title: 'Mã đã tồn tại', text: result.value.message, showCancelButton: true, confirmButtonText: 'Dùng sản phẩm có sẵn', cancelButtonText: 'Đóng' });
            if (use.isConfirmed) selectImportProduct(result.value.existing, input, hiddenInput, onSelectCallback);
            return;
        }
        selectImportProduct(result.value.product, input, hiddenInput, onSelectCallback);
    }
