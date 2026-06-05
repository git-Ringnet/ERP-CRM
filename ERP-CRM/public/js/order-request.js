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
            <select name="order_request_items[${i}][vendor_id]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                <option value="">-- Chọn --</option>
                ${suppliers.map(s => `<option value="${s.id}" ${data ? (data.vendor_id == s.id ? 'selected' : '') : (selectedVendor == s.id ? 'selected' : '')}>${s.name}</option>`).join('')}
            </select>
        </td>
        <td class="px-1 py-1.5">
            <select name="order_request_items[${i}][type]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                <option value="">-- Chọn --</option>
                ${types.map(t => `<option value="${t}" ${data ? (data.type == t ? 'selected' : '') : (selectedType ? (selectedType == t ? 'selected' : '') : (t === 'Hardware' ? 'selected' : ''))}>${t}</option>`).join('')}
            </select>
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
            <input type="text" name="order_request_items[${i}][eu_name]" required placeholder="EU Name" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][mst]" required placeholder="MST" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][address]" placeholder="Address" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5 text-center">
            <button type="button" onclick="removeOrderRequestRow(this)" class="text-red-400 hover:text-red-600">
                <i class="fas fa-trash-alt text-xs"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
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
