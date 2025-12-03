// Sample data
let customers = [
    { id: 1, code: 'KH001', name: 'Công ty TNHH ABC', email: 'abc@company.com', phone: '0901234567', address: 'Hà Nội', type: 'vip', taxCode: '0123456789', website: 'abc.com', contactPerson: 'Nguyễn Văn A', debtLimit: 200000000, debtDays: 30, note: '' },
    { id: 2, code: 'KH002', name: 'Công ty CP XYZ', email: 'xyz@company.com', phone: '0912345678', address: 'TP.HCM', type: 'normal', taxCode: '9876543210', website: 'xyz.com', contactPerson: 'Trần Thị B', debtLimit: 100000000, debtDays: 15, note: '' },
    { id: 3, code: 'KH003', name: 'Công ty TNHH DEF', email: 'def@company.com', phone: '0923456789', address: 'Đà Nẵng', type: 'normal', taxCode: '1122334455', website: 'def.com', contactPerson: 'Lê Văn C', debtLimit: 50000000, debtDays: 7, note: '' }
];

let editingId = null;

// Load customers on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCustomers();
    setupSearch();
    setupFilter();
});

// Load customers to table
function loadCustomers(filter = '') {
    const tbody = document.getElementById('customerTableBody');
    let filteredCustomers = customers;

    // Apply filter
    if (filter) {
        filteredCustomers = customers.filter(c => c.type === filter);
    }

    tbody.innerHTML = '';
    filteredCustomers.forEach(customer => {
        const row = `
            <tr>
                <td>${customer.code}</td>
                <td>${customer.name}</td>
                <td>${customer.email}</td>
                <td>${customer.phone}</td>
                <td>${customer.address}</td>
                <td><span class="badge badge-${customer.type}">${customer.type === 'vip' ? 'VIP' : 'Thường'}</span></td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewCustomer(${customer.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon btn-edit" onclick="editCustomer(${customer.id})" title="Sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteCustomer(${customer.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

// Setup search
function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#customerTableBody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// Setup filter
function setupFilter() {
    const filterSelect = document.getElementById('filterType');
    filterSelect.addEventListener('change', function() {
        loadCustomers(this.value);
    });
}

// Open add customer modal
function openAddCustomerModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm khách hàng mới';
    document.getElementById('customerForm').reset();
    document.getElementById('customerModal').style.display = 'block';
}

// Close modal
function closeCustomerModal() {
    document.getElementById('customerModal').style.display = 'none';
    editingId = null;
}

// View customer
function viewCustomer(id) {
    const customer = customers.find(c => c.id === id);
    if (customer) {
        alert(`Thông tin khách hàng:\n\nMã: ${customer.code}\nTên: ${customer.name}\nEmail: ${customer.email}\nĐiện thoại: ${customer.phone}\nĐịa chỉ: ${customer.address}\nLoại: ${customer.type === 'vip' ? 'VIP' : 'Thường'}\nMã số thuế: ${customer.taxCode}\nNgười liên hệ: ${customer.contactPerson}\nHạn mức công nợ: ${formatCurrency(customer.debtLimit)}\nSố ngày công nợ: ${customer.debtDays} ngày`);
    }
}

// Edit customer
function editCustomer(id) {
    const customer = customers.find(c => c.id === id);
    if (customer) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa khách hàng';
        
        document.getElementById('customerId').value = customer.id;
        document.getElementById('customerCode').value = customer.code;
        document.getElementById('customerName').value = customer.name;
        document.getElementById('customerEmail').value = customer.email;
        document.getElementById('customerPhone').value = customer.phone;
        document.getElementById('customerAddress').value = customer.address;
        document.getElementById('customerType').value = customer.type;
        document.getElementById('customerTaxCode').value = customer.taxCode;
        document.getElementById('customerWebsite').value = customer.website;
        document.getElementById('customerContactPerson').value = customer.contactPerson;
        document.getElementById('customerDebtLimit').value = customer.debtLimit;
        document.getElementById('customerDebtDays').value = customer.debtDays;
        document.getElementById('customerNote').value = customer.note;
        
        document.getElementById('customerModal').style.display = 'block';
    }
}

// Delete customer
function deleteCustomer(id) {
    const customer = customers.find(c => c.id === id);
    if (customer && confirm(`Bạn có chắc chắn muốn xóa khách hàng "${customer.name}"?`)) {
        customers = customers.filter(c => c.id !== id);
        loadCustomers();
        alert('Đã xóa khách hàng thành công!');
    }
}

// Save customer
function saveCustomer(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        code: formData.get('code'),
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        address: formData.get('address'),
        type: formData.get('type'),
        taxCode: formData.get('taxCode'),
        website: formData.get('website'),
        contactPerson: formData.get('contactPerson'),
        debtLimit: parseInt(formData.get('debtLimit')) || 0,
        debtDays: parseInt(formData.get('debtDays')) || 0,
        note: formData.get('note')
    };
    
    if (editingId) {
        // Update existing customer
        const index = customers.findIndex(c => c.id === editingId);
        if (index !== -1) {
            customers[index] = { ...customers[index], ...data };
            alert('Đã cập nhật thông tin khách hàng!');
        }
    } else {
        // Add new customer
        const newId = Math.max(...customers.map(c => c.id), 0) + 1;
        customers.push({ id: newId, ...data });
        alert('Đã thêm khách hàng mới!');
    }
    
    loadCustomers();
    closeCustomerModal();
}

// Export data
function exportData() {
    alert('Chức năng xuất Excel đang được phát triển');
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('customerModal');
    if (event.target == modal) {
        closeCustomerModal();
    }
}
