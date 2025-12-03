let data = [
    { id: 1, code: 'BG001', name: 'Bảng giá VIP Q4/2025', type: 'vip', startDate: '2025-10-01', endDate: '2025-12-31', status: 'active', products: [{name: 'Laptop Dell XPS 15', originalPrice: 25000000, price: 23000000, discount: 8}], description: 'Bảng giá ưu đãi cho khách hàng VIP', note: '' },
    { id: 2, code: 'BG002', name: 'Bảng giá thường Q4/2025', type: 'normal', startDate: '2025-10-01', endDate: '2025-12-31', status: 'active', products: [{name: 'Laptop Dell XPS 15', originalPrice: 25000000, price: 25000000, discount: 0}], description: 'Bảng giá chuẩn', note: '' }
];

let editingId = null;
let productRowCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
    setupFilter();
});

function loadData(filter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    if (filter) filteredData = data.filter(d => d.type === filter);
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const statusBadge = item.status === 'active' ? '<span class="badge badge-approved">Đang áp dụng</span>' : '<span class="badge badge-rejected">Hết hạn</span>';
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${getTypeText(item.type)}</td>
                <td>${formatDate(item.startDate)}</td>
                <td>${formatDate(item.endDate)}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon" onclick="copyData(${item.id})"><i class="fas fa-copy"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function getTypeText(type) {
    const types = {
        'vip': 'Khách hàng VIP',
        'normal': 'Khách hàng thường',
        'promotion': 'Khuyến mãi'
    };
    return types[type] || '';
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
    document.getElementById('filterType').addEventListener('change', function() {
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
                <label>Giá gốc</label>
                <input type="number" name="products[${productRowCount}][originalPrice]" min="0" required>
            </div>
            <div class="form-group">
                <label>Giá bán</label>
                <input type="number" name="products[${productRowCount}][price]" min="0" required>
            </div>
            <div class="form-group">
                <label>Chiết khấu (%)</label>
                <input type="number" name="products[${productRowCount}][discount]" min="0" max="100" value="0">
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
}

function openAddModal() {
    editingId = null;
    productRowCount = 1;
    document.getElementById('modalTitle').textContent = 'Thêm bảng giá mới';
    document.getElementById('dataForm').reset();
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
                    <label>Giá gốc</label>
                    <input type="number" name="products[0][originalPrice]" min="0" required>
                </div>
                <div class="form-group">
                    <label>Giá bán</label>
                    <input type="number" name="products[0][price]" min="0" required>
                </div>
                <div class="form-group">
                    <label>Chiết khấu (%)</label>
                    <input type="number" name="products[0][discount]" min="0" max="100" value="0">
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
        let productList = item.products.map(p => `- ${p.name}: ${formatCurrency(p.originalPrice)} → ${formatCurrency(p.price)} (CK: ${p.discount}%)`).join('\n');
        alert(`Thông tin bảng giá:\n\nMã: ${item.code}\nTên: ${item.name}\nLoại: ${getTypeText(item.type)}\nÁp dụng: ${formatDate(item.startDate)} - ${formatDate(item.endDate)}\n\nSản phẩm:\n${productList}`);
    }
}

function editData(id) {
    alert('Chức năng chỉnh sửa bảng giá đang được phát triển');
}

function copyData(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Bạn có muốn sao chép bảng giá "${item.name}"?`)) {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        const newCode = `BG${String(newId).padStart(3, '0')}`;
        data.push({ ...item, id: newId, code: newCode, name: item.name + ' (Copy)' });
        loadData();
        alert('Đã sao chép bảng giá!');
    }
}

function deleteData(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Bạn có chắc chắn muốn xóa bảng giá "${item.name}"?`)) {
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
            originalPrice: parseFloat(formData.get(`products[${index}][originalPrice]`)),
            price: parseFloat(formData.get(`products[${index}][price]`)),
            discount: parseFloat(formData.get(`products[${index}][discount]`))
        });
    });
    
    const newData = {
        code: formData.get('code'),
        name: formData.get('name'),
        type: formData.get('type'),
        startDate: formData.get('startDate'),
        endDate: formData.get('endDate'),
        products: products,
        description: formData.get('description'),
        status: 'active',
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = data.findIndex(d => d.id === editingId);
        if (index !== -1) {
            data[index] = { ...data[index], ...newData };
            alert('Đã cập nhật bảng giá!');
        }
    } else {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        data.push({ id: newId, ...newData });
        alert('Đã tạo bảng giá mới!');
    }
    
    loadData();
    closeModal();
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
