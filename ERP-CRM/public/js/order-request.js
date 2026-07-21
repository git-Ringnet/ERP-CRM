/**
 * Order Request Modal Functions
 * Shared across sales/show, sales/create, sales/edit
 */

// ==========================================
// Configuration - injected from Blade
// ==========================================
// window.OR_VENDORS and window.OR_TYPES must be set before this script loads

let orderRequestRowIndex = 1;

// ==========================================
// Modal open/close (for show page)
// ==========================================
function openOrderRequestModal() {
    const modal = document.getElementById('orderRequestModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeOrderRequestModal() {
    const modal = document.getElementById('orderRequestModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Close modal on backdrop click
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('orderRequestModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeOrderRequestModal();
        });
    }
});

// ==========================================
// Inline accordion toggle (for create/edit)
// ==========================================
function toggleOrderRequestSection() {
    const section = document.getElementById('orderRequestSection');
    const icon = document.getElementById('orderRequestToggleIcon');
    if (section) {
        section.classList.toggle('hidden');
        if (icon) {
            icon.classList.toggle('rotate-180');
        }
    }
}

// ==========================================
// Dynamic row add/remove
// ==========================================
function buildSelectOptions(list, isObject = false) {
    let html = `<option value="">-- Chọn --</option>`;
    list.forEach(item => { 
        if (isObject) {
            html += `<option value="${item.id}">${item.name}</option>`; 
        } else {
            html += `<option value="${item}">${item}</option>`; 
        }
    });
    return html;
}

function applyGlobalVendorType(context) {
    const vendorId = `global_vendor_id_${context}`;
    const typeId = `global_type_${context}`;
    const globalVendor = document.getElementById(vendorId).value;
    const globalType = document.getElementById(typeId).value;

    if (!globalVendor && !globalType) {
        alert('Vui lòng chọn Vendor hoặc Type trước khi áp dụng.');
        return;
    }

    const tbody = document.getElementById('orderRequestRows');
    if (!tbody) return;

    if (globalVendor) {
        tbody.querySelectorAll('select[name$="[vendor_id]"]').forEach(select => {
            select.value = globalVendor;
        });
    }

    if (globalType) {
        tbody.querySelectorAll('select[name$="[type]"]').forEach(select => {
            select.value = globalType;
        });
    }

    // Trigger handleVendorTypeChange for all rows to show/hide CQ checkbox
    tbody.querySelectorAll('.order-request-row').forEach(row => {
        handleVendorTypeChange(row);
    });
}

function addOrderRequestRow(data = null) {
    const i = orderRequestRowIndex++;
    const tbody = document.getElementById('orderRequestRows');
    if (!tbody) return;

    const suppliers = window.OR_SUPPLIERS || [];
    const types = window.OR_TYPES || [];

    let selectedVendor = '';
    let selectedType = '';

    const modalVendorEl = document.getElementById('global_vendor_id_modal');
    const formVendorEl = document.getElementById('global_vendor_id_form');

    if (modalVendorEl) {
        selectedVendor = modalVendorEl.value;
        const typeEl = document.getElementById('global_type_modal');
        if (typeEl) selectedType = typeEl.value;
    } else if (formVendorEl) {
        selectedVendor = formVendorEl.value;
        const typeEl = document.getElementById('global_type_form');
        if (typeEl) selectedType = typeEl.value;
    }

    const tr = document.createElement('tr');
    tr.className = 'order-request-row border-b border-gray-100 hover:bg-gray-50';
    tr.dataset.index = i;
    tr.innerHTML = `
        <td class="px-1 py-1.5">
            <select name="order_request_items[${i}][vendor_id]" required class="vendor-select w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400" onchange="handleVendorTypeChange(this.closest('.order-request-row'))">
                <option value="">-- Chọn --</option>
                ${suppliers.map(s => `<option value="${s.id}" data-name="${s.name}" ${data ? (data.vendor_id == s.id ? 'selected' : '') : (selectedVendor == s.id ? 'selected' : '')}>${s.name}</option>`).join('')}
            </select>
        </td>
        <td class="px-1 py-1.5">
            <select name="order_request_items[${i}][type]" required class="type-select w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400" onchange="handleVendorTypeChange(this.closest('.order-request-row'))">
                <option value="">-- Chọn --</option>
                ${types.map(t => `<option value="${t}" ${data ? (data.type == t ? 'selected' : '') : (selectedType ? (selectedType == t ? 'selected' : '') : (t === 'Hardware' ? 'selected' : ''))}>${t}</option>`).join('')}
            </select>
        </td>
        <td class="px-1 py-1.5 text-center cq-checkbox-cell">
            <label class="cq-checkbox-label inline-flex items-center gap-1 cursor-pointer" style="display:none;" title="Tick nếu cần cấp CQ riêng">
                <input type="checkbox" name="order_request_items[${i}][needs_cq]" value="1"
                    class="needs-cq-checkbox w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                    onchange="handleNeedsCqChange(this.closest('.order-request-row'))">
                <span class="text-[10px] text-gray-600">CQ</span>
            </label>
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][part_number]" value="${data ? data.part_number : ''}" required placeholder="P/N" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
            <input type="hidden" name="order_request_items[${i}][product_id]" value="${data ? data.product_id : ''}">
        </td>
        <td class="px-1 py-1.5">
            <input type="number" name="order_request_items[${i}][quantity]" value="${data ? data.quantity : 1}" required step="0.01" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400 text-center">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][unit]" value="${data ? (data.unit || '') : ''}" placeholder="Đơn vị" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][serial_number]" placeholder="SN" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][exp_date]" placeholder="YYYY-MM-DD" class="exp-date-picker w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][si_name]" required placeholder="SI Name" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][pos_id]" value="${data ? (data.pos_id || '') : ''}" placeholder="POS ID" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5 eu-field">
            <input type="text" name="order_request_items[${i}][eu_name]" placeholder="EU Name" class="eu-name-input w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5 eu-field">
            <input type="text" name="order_request_items[${i}][mst]" placeholder="MST" class="mst-input w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5 eu-field">
            <input type="text" name="order_request_items[${i}][address]" placeholder="Address" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5 text-center">
            <button type="button" onclick="removeOrderRequestRow(this)" class="text-red-400 hover:text-red-600">
                <i class="fas fa-trash-alt text-xs"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    handleVendorTypeChange(tr);
    if (typeof window.initExpDatePicker === 'function') {
        window.initExpDatePicker(tr.querySelector('.exp-date-picker'));
    } else if (typeof flatpickr !== 'undefined') {
        flatpickr(tr.querySelector('.exp-date-picker'), {
            dateFormat: "Y-m-d",
            allowInput: true
        });
    }
}

function removeOrderRequestRow(btn) {
    const rows = document.querySelectorAll('.order-request-row');
    if (rows.length > 1) {
        btn.closest('tr').remove();
    } else {
        // Clear inputs instead of removing last row
        const row = btn.closest('tr');
        row.querySelectorAll('input').forEach(i => i.value = '');
        row.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    }
}

function importFromItems() {
    const items = window.OR_SALE_ITEMS || [];
    if (items.length === 0) {
        alert('Không tìm thấy sản phẩm trong đơn hàng này.');
        return;
    }

    if (confirm(`Bạn muốn tự động điền ${items.length} sản phẩm từ đơn hàng vào yêu cầu đặt hàng?`)) {
        const tbody = document.getElementById('orderRequestRows');
        tbody.innerHTML = ''; // Clear all
        orderRequestRowIndex = 0;
        
        items.forEach(item => {
            addOrderRequestRow({
                product_id: item.product_id,
                part_number: item.part_number,
                quantity: item.quantity,
                unit: item.unit,
                vendor_id: item.vendor_id,
                type: 'Hardware'
            });
        });
    }
}

/**
 * Check if the selected vendor is Fortinet
 */
function isFortinetVendor(row) {
    const vendorSelect = row.querySelector('.vendor-select');
    if (!vendorSelect || !vendorSelect.value) return false;
    const selectedOption = vendorSelect.options[vendorSelect.selectedIndex];
    const vendorName = selectedOption ? (selectedOption.getAttribute('data-name') || selectedOption.textContent) : '';
    return vendorName.toLowerCase().includes('fortinet');
}

/**
 * Handle vendor or type dropdown change:
 * - If vendor=Fortinet AND type=HW → show CQ checkbox, hide EU fields (unless CQ is checked)
 * - Otherwise → hide CQ checkbox, show EU fields as required
 */
function handleVendorTypeChange(row) {
    const typeSelect = row.querySelector('.type-select');
    const cqLabel = row.querySelector('.cq-checkbox-label');
    const cqCheckbox = row.querySelector('.needs-cq-checkbox');
    const euFields = row.querySelectorAll('.eu-field');
    const euNameInput = row.querySelector('.eu-name-input');
    const mstInput = row.querySelector('.mst-input');
    const addrInput = row.querySelector('input[name$="[address]"]');

    if (!cqLabel || !typeSelect) return;

    // Auto set unit based on type
    const unitInput = row.querySelector('input[name$="[unit]"]');
    if (unitInput) {
        if (typeSelect.value === 'HW') {
            unitInput.value = 'Cái';
        } else if (typeSelect.value && typeSelect.value.toLowerCase().startsWith('lic')) {
            unitInput.value = 'Bộ';
        }
    }

    const isFTN = isFortinetVendor(row);
    const isHW = typeSelect.value === 'HW';

    if (isFTN && isHW) {
        // Show CQ checkbox label (Ask: Cần cấp CQ riêng?)
        cqLabel.style.display = '';
        
        // If CQ not checked → hide/dim EU fields (Stock / Runrate item without CQ)
        if (!cqCheckbox.checked) {
            euFields.forEach(td => {
                td.style.opacity = '0.3';
                const inputs = td.querySelectorAll('input');
                inputs.forEach(inp => {
                    inp.removeAttribute('required');
                    inp.setAttribute('tabindex', '-1');
                });
            });
            // Clear EU fields for stock item
            if (euNameInput) euNameInput.value = '';
            if (mstInput) mstInput.value = '';
            if (addrInput) addrInput.value = '';
        } else {
            // CQ checked → show EU fields
            euFields.forEach(td => {
                td.style.opacity = '1';
                const inputs = td.querySelectorAll('input');
                inputs.forEach(inp => inp.removeAttribute('tabindex'));
            });
            autoFillEuFromGlobal(row);
        }
    } else {
        // Non-Fortinet or non-HW: hide CQ checkbox label, show EU fields
        cqLabel.style.display = 'none';
        cqCheckbox.checked = false;
        
        euFields.forEach(td => {
            td.style.opacity = '1';
            const inputs = td.querySelectorAll('input');
            inputs.forEach(inp => inp.removeAttribute('tabindex'));
        });
        autoFillEuFromGlobal(row);
    }
}

/**
 * Handle CQ checkbox change:
 * - Checked → show EU fields, fill EU info from global
 * - Unchecked → dim EU fields & clear (Stock / Runrate item)
 */
function handleNeedsCqChange(row) {
    const cqCheckbox = row.querySelector('.needs-cq-checkbox');
    const euFields = row.querySelectorAll('.eu-field');
    const euNameInput = row.querySelector('.eu-name-input');
    const mstInput = row.querySelector('.mst-input');
    const addrInput = row.querySelector('input[name$="[address]"]');

    if (cqCheckbox.checked) {
        euFields.forEach(td => {
            td.style.opacity = '1';
            const inputs = td.querySelectorAll('input');
            inputs.forEach(inp => inp.removeAttribute('tabindex'));
        });
        autoFillEuFromGlobal(row);
    } else {
        euFields.forEach(td => {
            td.style.opacity = '0.3';
            const inputs = td.querySelectorAll('input');
            inputs.forEach(inp => {
                inp.removeAttribute('required');
                inp.setAttribute('tabindex', '-1');
            });
        });
        // Clear EU fields for stock item
        if (euNameInput) euNameInput.value = '';
        if (mstInput) mstInput.value = '';
        if (addrInput) addrInput.value = '';
    }
}

function autoFillEuFromGlobal(row) {
    const euNameInput = row.querySelector('.eu-name-input');
    const mstInput = row.querySelector('.mst-input');
    const addrInput = row.querySelector('input[name$="[address]"]');

    const globalEuEl = document.getElementById('global_eu_name');
    const globalMstEl = document.getElementById('global_mst');
    const globalAddrEl = document.getElementById('global_address');

    const globalEu = globalEuEl ? globalEuEl.value : '';
    const globalMst = globalMstEl ? globalMstEl.value : '';
    const globalAddr = globalAddrEl ? globalAddrEl.value : '';

    if (euNameInput && !euNameInput.value) euNameInput.value = globalEu;
    if (mstInput && !mstInput.value) mstInput.value = globalMst;
    if (addrInput && !addrInput.value) addrInput.value = globalAddr;
}

// Initial initialization for existing edit rows on load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.order-request-row').forEach(row => {
        handleVendorTypeChange(row);
    });
});
