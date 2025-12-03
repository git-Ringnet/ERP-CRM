// Module Navigation
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function() {
        // Remove active class from all nav items
        document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
        
        // Add active class to clicked item
        this.classList.add('active');
        
        // Hide all module contents
        document.querySelectorAll('.module-content').forEach(module => module.classList.remove('active'));
        
        // Show selected module content
        const moduleId = this.getAttribute('data-module');
        const moduleElement = document.getElementById(moduleId);
        if (moduleElement) {
            moduleElement.classList.add('active');
        }
    });
});

// Customer Management
function openAddCustomerModal() {
    document.getElementById('customerModalTitle').textContent = 'Thêm khách hàng mới';
    document.getElementById('customerForm').reset();
    document.getElementById('customerModal').style.display = 'block';
}

function closeCustomerModal() {
    document.getElementById('customerModal').style.display = 'none';
}

function editCustomer(id) {
    document.getElementById('customerModalTitle').textContent = 'Chỉnh sửa khách hàng';
    // Load customer data here
    document.getElementById('customerModal').style.display = 'block';
}

function deleteCustomer(id) {
    if (confirm('Bạn có chắc chắn muốn xóa khách hàng này?')) {
        alert('Đã xóa khách hàng ID: ' + id);
        // Delete logic here
    }
}

function saveCustomer(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);
    console.log('Saving customer:', data);
    alert('Đã lưu thông tin khách hàng!');
    closeCustomerModal();
    // Save logic here
}

// Supplier Management
function openAddSupplierModal() {
    alert('Mở form thêm nhà cung cấp');
    // Similar to customer modal
}

// Employee Management
function openAddEmployeeModal() {
    alert('Mở form thêm nhân viên');
    // Similar to customer modal
}

// Product Management
function openAddProductModal() {
    alert('Mở form thêm sản phẩm');
    // Similar to customer modal
}

// Order Management
function openAddOrderModal() {
    alert('Mở form tạo đơn hàng');
    // Similar to customer modal
}

function exportOrders() {
    alert('Xuất danh sách đơn hàng ra Excel');
}

function approveOrder(orderId, level) {
    if (confirm(`Bạn có chắc chắn muốn duyệt đơn hàng này (Cấp ${level})?`)) {
        alert(`Đã duyệt đơn hàng ID: ${orderId} - Cấp ${level}`);
    }
}

function rejectOrder(orderId) {
    const reason = prompt('Nhập lý do từ chối:');
    if (reason) {
        alert(`Đã từ chối đơn hàng ID: ${orderId}\nLý do: ${reason}`);
    }
}

// Price List
function openAddPriceListModal() {
    alert('Mở form thêm bảng giá');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const customerModal = document.getElementById('customerModal');
    if (event.target == customerModal) {
        customerModal.style.display = 'none';
    }
}

// Logout
document.querySelector('.btn-logout').addEventListener('click', function() {
    if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        alert('Đã đăng xuất!');
        // Add logout logic here
    }
});

// Search functionality
document.querySelectorAll('.search-input').forEach(input => {
    input.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const table = this.closest('.module-content').querySelector('tbody');
        if (table) {
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
    });
});

// Sample data for demonstration
const sampleCustomers = [
    { id: 1, code: 'KH001', name: 'Công ty TNHH ABC', email: 'abc@company.com', phone: '0901234567', address: 'Hà Nội', type: 'vip' },
    { id: 2, code: 'KH002', name: 'Công ty CP XYZ', email: 'xyz@company.com', phone: '0912345678', address: 'TP.HCM', type: 'normal' },
    { id: 3, code: 'KH003', name: 'Công ty TNHH DEF', email: 'def@company.com', phone: '0923456789', address: 'Đà Nẵng', type: 'normal' }
];

const sampleOrders = [
    { id: 1, code: 'DH001', customer: 'Công ty TNHH ABC', date: '09/11/2025', total: '100,000,000đ', status: 'pending' },
    { id: 2, code: 'DH002', customer: 'Công ty CP XYZ', date: '08/11/2025', total: '50,000,000đ', status: 'approved' },
    { id: 3, code: 'DH003', customer: 'Công ty TNHH DEF', date: '07/11/2025', total: '75,000,000đ', status: 'rejected' }
];

// Tab switching
function switchTab(tabName) {
    // Remove active class from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class to selected tab
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

function switchApprovalTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabName + '-approval').classList.add('active');
}

function switchMarginTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabName + '-margin').classList.add('active');
}

// Organization Chart
function viewDepartment(deptId) {
    alert('Xem chi tiết phòng ban: ' + deptId);
}

function openAddDepartmentModal() {
    alert('Mở form thêm phòng ban');
}

// Asset Management
function openAddAssetModal() {
    alert('Mở form thêm tài sản');
}

function viewProductDetail(id) {
    alert('Xem chi tiết sản phẩm ID: ' + id);
}

function generateSerialNumber() {
    const year = new Date().getFullYear();
    const month = String(new Date().getMonth() + 1).padStart(2, '0');
    const random = String(Math.floor(Math.random() * 999999)).padStart(6, '0');
    const serial = `SN-${year}-${random}`;
    
    if (confirm(`Tạo serial number mới: ${serial}?`)) {
        alert(`Đã tạo serial: ${serial}`);
    }
}

function generateLotNumber() {
    const year = new Date().getFullYear();
    const month = String(new Date().getMonth() + 1).padStart(2, '0');
    const random = String(Math.floor(Math.random() * 999)).padStart(3, '0');
    const lot = `LOT-${year}-${month}-${random}`;
    
    if (confirm(`Tạo số lô mới: ${lot}?`)) {
        alert(`Đã tạo số lô: ${lot}`);
    }
}

function generateInternalSerial() {
    const productCode = prompt('Nhập mã sản phẩm (VD: SP001):');
    if (productCode) {
        const year = new Date().getFullYear();
        const month = String(new Date().getMonth() + 1).padStart(2, '0');
        const random = String(Math.floor(Math.random() * 99999)).padStart(5, '0');
        const serial = `${productCode}-${year}-${month}-${random}`;
        
        alert(`Đã tạo serial nội bộ: ${serial}`);
    }
}

// Initialize data on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Mini ERP/CRM System loaded');
    // You can load initial data here
});
