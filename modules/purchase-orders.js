let data = [
    { id: 1, code: 'PO001', supplier: 'Nhà cung cấp A', date: '2025-11-09', subtotal: 200000000, discount: 5, shippingCost: 5000000, otherCost: 1000000, vat: 10, total: 215500000, status: 'ordered', deliveryAddress: 'Kho chính - Hà Nội', products: [{name: 'Laptop Dell XPS 15', quantity: 10, price: 20000000, total: 200000000}], paymentTerms: 'Thanh toán trong 30 ngày', note: '' },
    { id: 2, code: 'PO002', supplier: 'Nhà cung cấp B', date: '2025-11-08', subtotal: 100000000, discount: 3, shippingCost: 3000000, otherCost: 500000, vat: 10, total: 110050000, status: 'requested', deliveryAddress: 'Kho phụ - TP.HCM', products: [{name: 'Màn hình LG 27 inch', quantity: 20, price: 5000000, total: 100000000}], paymentTerms: 'Thanh toán trong 15 ngày', note: '' }
];

let editingId = null;
let productRowCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
    setupFilter();
    setDefaultDate();
});

function setDefaultDate() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('[name="date"]').value = today;
}

function loadData(statusFilter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    if (statusFilter) filteredData = data.filter(d => d.status === statusFilter);
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const statusBadge = getStatusBadge(item.status);
        const serviceCost = item.shippingCost + item.otherCost;
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.supplier}</td>
                <td>${formatDate(item.date)}</td>
                <td>${formatCurrency(item.total)}</td>
                <td>${formatCurrency(serviceCost)}</td>
                <td>${item.discount}%</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})" title="Xem"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})" title="Sửa"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon" onclick="sendEmail(${item.id})" title="Gửi email"><i class="fas fa-envelope"></i></button>
                    <button class="btn-icon" onclick="receiveOrder(${item.id})" title="Nhận hàng"><i class="fas fa-box"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})" title="Xóa"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function getStatusBadge(status) {
    const badges = {
        'draft': '<span class="badge badge-normal">Nháp</span>',
        'requested': '<span class="badge badge-pending">Yêu cầu báo giá</span>',
        'quoted': '<span class="badge badge-serial">Đã có báo giá</span>',
        'ordered': '<span class="badge badge-approved">Đã đặt hàng</span>',
        'received': '<span class="badge badge-approved">Đã nhận hàng</span>',
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

function setupFilter() {
    document.getElementById('filterStatus').addEventListener('change', function() {
        loadData(this.value);
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
    
    document.querySelectorAll('.product-item').forEach(item => {
        const quantity = parseFloat(item.querySelector('[name*="[quantity]"]').value) || 0;
        const price = parseFloat(item.querySelector('[name*="[price]"]').value) || 0;
        const total = quantity * price;
        item.querySelector('[name*="[total]"]').value = total;
        subtotal += total;
    });
    
    document.getElementById('subtotal').value = subtotal;
    
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const shippingCost = parseFloat(document.getElementById('shippingCost').value) || 0;
    const otherCost = parseFloat(document.getElementById('otherCost').value) || 0;
    const vat = parseFloat(document.getElementById('vat').value) || 0;
    
    const afterDiscount = subtotal * (1 - discount / 100);
    const beforeVat = afterDiscount + shippingCost + otherCost;
    const total = beforeVat * (1 + vat / 100);
    
    document.getElementById('total').value = Math.round(total);
}

function openAddModal() {
    editingId = null;
    productRowCount = 1;
    document.getElementById('modalTitle').textContent = 'Tạo đơn mua hàng mới';
    document.getElementById('dataForm').reset();
    setDefaultDate();
    
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
        alert(`Thông tin đơn mua hàng:\n\nMã: ${item.code}\nNhà cung cấp: ${item.supplier}\nNgày: ${formatDate(item.date)}\n\nSản phẩm:\n${productList}\n\nTổng tiền: ${formatCurrency(item.total)}\nChiết khấu: ${item.discount}%\nChi phí vận chuyển: ${formatCurrency(item.shippingCost)}\nTrạng thái: ${getStatusText(item.status)}`);
    }
}

function getStatusText(status) {
    const texts = {
        'draft': 'Nháp',
        'requested': 'Yêu cầu báo giá',
        'quoted': 'Đã có báo giá',
        'ordered': 'Đã đặt hàng',
        'received': 'Đã nhận hàng',
        'cancelled': 'Đã hủy'
    };
    return texts[status] || '';
}

function editData(id) {
    alert('Chức năng chỉnh sửa đơn mua hàng đang được phát triển');
}

function deleteData(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Bạn có chắc chắn muốn xóa đơn mua hàng "${item.code}"?`)) {
        data = data.filter(d => d.id !== id);
        loadData();
        alert('Đã xóa thành công!');
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
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
        supplier: formData.get('supplier'),
        date: formData.get('date'),
        deliveryAddress: formData.get('deliveryAddress'),
        products: products,
        subtotal: parseFloat(formData.get('subtotal')),
        discount: parseFloat(formData.get('discount')),
        shippingCost: parseFloat(formData.get('shippingCost')),
        otherCost: parseFloat(formData.get('otherCost')),
        vat: parseFloat(formData.get('vat')),
        total: parseFloat(formData.get('total')),
        status: formData.get('status'),
        paymentTerms: formData.get('paymentTerms'),
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = data.findIndex(d => d.id === editingId);
        if (index !== -1) {
            data[index] = { ...data[index], ...newData };
            alert('Đã cập nhật đơn mua hàng!');
        }
    } else {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        data.push({ id: newId, ...newData });
        alert('Đã tạo đơn mua hàng mới!');
    }
    
    loadData();
    closeModal();
}

function saveAndSendEmail() {
    alert('Đơn mua hàng đã được lưu và gửi email cho nhà cung cấp!');
}

function sendEmail(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Đã gửi email đơn mua hàng ${item.code} cho nhà cung cấp ${item.supplier}`);
    }
}

function receiveOrder(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Xác nhận đã nhận hàng cho đơn ${item.code}?`)) {
        const index = data.findIndex(d => d.id === id);
        if (index !== -1) {
            data[index].status = 'received';
            loadData();
            alert('Đã cập nhật trạng thái nhận hàng!');
        }
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
