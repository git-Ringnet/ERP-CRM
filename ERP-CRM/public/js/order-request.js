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
function buildSelectOptions(list) {
    let html = `<option value="">-- Chọn --</option>`;
    list.forEach(v => { html += `<option value="${v}">${v}</option>`; });
    return html;
}

function addOrderRequestRow() {
    const i = orderRequestRowIndex++;
    const tbody = document.getElementById('orderRequestRows');
    if (!tbody) return;

    const vendors = window.OR_VENDORS || [];
    const types = window.OR_TYPES || [];

    const tr = document.createElement('tr');
    tr.className = 'order-request-row border-b border-gray-100 hover:bg-gray-50';
    tr.dataset.index = i;
    tr.innerHTML = `
        <td class="px-1 py-1.5">
            <select name="order_request_items[${i}][vendor]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                ${buildSelectOptions(vendors)}
            </select>
        </td>
        <td class="px-1 py-1.5">
            <select name="order_request_items[${i}][type]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                ${buildSelectOptions(types)}
            </select>
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][part_number]" required placeholder="P/N" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][serial_number]" placeholder="SN" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="date" name="order_request_items[${i}][exp_date]" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][si_name]" required placeholder="SI Name" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
        </td>
        <td class="px-1 py-1.5">
            <input type="text" name="order_request_items[${i}][eu_name_mst]" required placeholder="EU Name - MST" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
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
}

function removeOrderRequestRow(btn) {
    const rows = document.querySelectorAll('.order-request-row');
    if (rows.length > 1) {
        btn.closest('tr').remove();
    }
}
