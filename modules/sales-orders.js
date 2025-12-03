let data = [
    { id: 1, code: 'DH001', type: 'retail', customer: 'Công ty TNHH ABC', date: '2025-11-09', total: 100000000, margin: 15, status: 'pending', deliveryAddress: 'Hà Nội', products: [{name: 'Laptop Dell XPS 15', quantity: 4, price: 25000000, total: 100000000}], subtotal: 100000000, discount: 0, vat: 10, note: '' },
    { id: 2, code: 'DH002', type: 'project', customer: 'Công ty CP XYZ', date: '2025-11-08', total: 500000000, margin: 12, status: 'approved', deliveryAddress: 'TP.HCM', products: [{name: 'Màn hình LG 27 inch', quantity: 100, price: 5000000, total: 500000000}], subtotal: 500000000, discount: 0, vat: 10, note: '' }
];

let editingId = null;
let productRowCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
    setupFilters();
    setDefaultDate();
});

function setDefaultDate() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('[name="date"]').value = today;
}

function loadData(statusFilter = '', typeFilter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    
    if (statusFilter) filteredData = filteredData.filter(d => d.status === statusFilter);
    if (typeFilter) filteredData = filteredData.filter(d => d.type === typeFilter);
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const typeBadge = item.type === 'retail' ? '<span class="badge badge-normal">Bán lẻ</span>' : '<span class="badge badge-vip">Dự án</span>';
        const statusBadge = getStatusBadge(item.status);
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${typeBadge}</td>
                <td>${item.customer}</td>
                <td>${formatDate(item.date)}</td>
                <td>${formatCurrency(item.total)}</td>
                <td><span class="text-success">${item.margin}%</span></td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})" title="Xem"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})" title="Sửa"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon" onclick="sendEmail(${item.id})" title="Gửi email"><i class="fas fa-envelope"></i></button>
                    <button class="btn-icon" onclick="exportInvoice(${item.id})" title="Xuất hóa đơn"><i class="fas fa-file-invoice"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})" title="Xóa"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge badge-pending">Chờ duyệt</span>',
        'approved': '<span class="badge badge-approved">Đã duyệt</span>',
        'shipping': '<span class="badge badge-pending">Đang giao</span>',
        'completed': '<span class="badge badge-approved">Hoàn thành</span>',
        'cancelled': '<span class="badge badge-rejected">Đã hủy</span>'
    };
    return badges[status] || '';
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#tableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
}

function setupFilters() {
    document.getElementById('filterStatus').addEventListener('change', function() {
        loadData(this.value, document.getElementById('filterType').value);
    });
    document.getElementById('filterType').addEventListener('change', function() {
        loadData(document.getElementById('filterStatus').value, this.value);
    });
}

function addProductRow() {
    const productList = document.getElementById('productList');
    const newRow = document.createElement('div');
    newRow.className = 'product-item';
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label>Sản phẩm</label>
                <select name="products[${productRowCount}][name]" required>
                    <option value="">Chọn sản phẩm</option>
                    <option value="Laptop Dell XPS 15">Laptop Dell XPS 15</option>
                    <option value="Màn hình LG 27 inch">Màn hình LG 27 inch</option>
                    <option value="Bàn làm việc">Bàn làm việc</option>
                </select>
            </div>
            <div class="form-group">
                <label>Số lượng</label>
                <input type="number" name="products[${productRowCount}][quantity]" min="1" value="1" required onchange="calculateTotal()">
            </div>
            <div class="form-group">
                <label>Đơn giá</label>
                <input type="number" name="products[${productRowCount}][price]" min="0" required onchange="calculateTotal()">
            </div>
            <div class="form-group">
                <label>Thành tiền</label>
                <input type="number" name="products[${productRowCount}][total]" readonly>
            </div>
            <div class="form-group" style="flex: 0;">
                <label>&nbsp;</label>
                <button type="button" class="btn-icon btn-delete" onclick="removeProductRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    productList.appendChild(newRow);
    productRowCount++;
}

function removeProductRow(button) {
    button.closest('.product-item').remove();
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    
    // Calculate each product total
    document.querySelectorAll('.product-item').forEach(item => {
        const quantity = parseFloat(item.querySelector('[name*="[quantity]"]').value) || 0;
        const price = parseFloat(item.querySelector('[name*="[price]"]').value) || 0;
        const total = quantity * price;
        item.querySelector('[name*="[total]"]').value = total;
        subtotal += total;
    });
    
    document.getElementById('subtotal').value = subtotal;
    
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const vat = parseFloat(document.getElementById('vat').value) || 0;
    
    const afterDiscount = subtotal * (1 - discount / 100);
    const total = afterDiscount * (1 + vat / 100);
    
    document.getElementById('total').value = Math.round(total);
}

function openAddModal() {
    editingId = null;
    productRowCount = 1;
    document.getElementById('modalTitle').textContent = 'Tạo đơn hàng mới';
    document.getElementById('dataForm').reset();
    setDefaultDate();
    
    // Reset product list
    document.getElementById('productList').innerHTML = `
        <div class="product-item">
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label>Sản phẩm</label>
                    <select name="products[0][name]" required>
                        <option value="">Chọn sản phẩm</option>
                        <option value="Laptop Dell XPS 15">Laptop Dell XPS 15</option>
                        <option value="Màn hình LG 27 inch">Màn hình LG 27 inch</option>
                        <option value="Bàn làm việc">Bàn làm việc</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Số lượng</label>
                    <input type="number" name="products[0][quantity]" min="1" value="1" required onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <label>Đơn giá</label>
                    <input type="number" name="products[0][price]" min="0" required onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <label>Thành tiền</label>
                    <input type="number" name="products[0][total]" readonly>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        let productList = item.products.map(p => `- ${p.name}: ${p.quantity} x ${formatCurrency(p.price)} = ${formatCurrency(p.total)}`).join('\n');
        alert(`Thông tin đơn hàng:\n\nMã: ${item.code}\nLoại: ${item.type === 'retail' ? 'Bán lẻ' : 'Dự án'}\nKhách hàng: ${item.customer}\nNgày: ${formatDate(item.date)}\n\nSản phẩm:\n${productList}\n\nTổng tiền: ${formatCurrency(item.total)}\nMargin: ${item.margin}%\nTrạng thái: ${getStatusText(item.status)}`);
    }
}

function getStatusText(status) {
    const texts = {
        'pending': 'Chờ duyệt',
        'approved': 'Đã duyệt',
        'shipping': 'Đang giao',
        'completed': 'Hoàn thành',
        'cancelled': 'Đã hủy'
    };
    return texts[status] || '';
}

function editData(id) {
    alert('Chức năng chỉnh sửa đơn hàng đang được phát triển');
}

function deleteData(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Bạn có chắc chắn muốn xóa đơn hàng "${item.code}"?`)) {
        data = data.filter(d => d.id !== id);
        loadData();
        alert('Đã xóa thành công!');
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    // Collect products
    const products = [];
    document.querySelectorAll('.product-item').forEach((item, index) => {
        products.push({
            name: formData.get(`products[${index}][name]`),
            quantity: parseFloat(formData.get(`products[${index}][quantity]`)),
            price: parseFloat(formData.get(`products[${index}][price]`)),
            total: parseFloat(formData.get(`products[${index}][total]`))
        });
    });
    
    const newData = {
        code: formData.get('code'),
        type: formData.get('type'),
        customer: formData.get('customer'),
        date: formData.get('date'),
        deliveryAddress: formData.get('deliveryAddress'),
        products: products,
        subtotal: parseFloat(formData.get('subtotal')),
        discount: parseFloat(formData.get('discount')),
        vat: parseFloat(formData.get('vat')),
        total: parseFloat(formData.get('total')),
        margin: 15, // Calculate from cost
        status: 'pending',
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = data.findIndex(d => d.id === editingId);
        if (index !== -1) {
            data[index] = { ...data[index], ...newData };
            alert('Đã cập nhật đơn hàng!');
        }
    } else {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        data.push({ id: newId, ...newData });
        alert('Đã tạo đơn hàng mới!');
    }
    
    loadData();
    closeModal();
}

function saveAndSendEmail() {
    alert('Đơn hàng đã được lưu và gửi email cho khách hàng!');
}

function sendEmail(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Đã gửi email đơn hàng ${item.code} cho khách hàng ${item.customer}`);
    }
}

function exportInvoice(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Đang xuất hóa đơn cho đơn hàng ${item.code}...`);
    }
}

function exportData() {
    alert('Chức năng xuất Excel đang được phát triển');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN');
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) {
        closeModal();
    }
}
