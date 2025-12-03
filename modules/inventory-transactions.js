let data = [
    { id: 1, code: 'NK001', type: 'import', warehouse: 'Kho chính', date: '2025-11-09', employee: 'Nguyễn Văn A', totalQty: 10, products: [{name: 'Laptop Dell XPS 15', quantity: 10, unit: 'Cái', serial: 'LOT-2025-11-001'}], reference: 'PO001', note: 'Nhập hàng từ nhà cung cấp A' },
    { id: 2, code: 'XK001', type: 'export', warehouse: 'Kho chính', date: '2025-11-09', employee: 'Trần Thị B', totalQty: 5, products: [{name: 'Laptop Dell XPS 15', quantity: 5, unit: 'Cái', serial: 'SN-2025-001234'}], reference: 'DH001', note: 'Xuất hàng cho đơn hàng DH001' },
    { id: 3, code: 'DC001', type: 'transfer', warehouse: 'Kho chính', toWarehouse: 'Kho phụ', date: '2025-11-08', employee: 'Lê Văn C', totalQty: 20, products: [{name: 'Màn hình LG 27 inch', quantity: 20, unit: 'Cái', serial: ''}], reference: '', note: 'Điều chuyển hàng sang kho phụ' }
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

function loadData(typeFilter = '', warehouseFilter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    
    if (typeFilter) filteredData = filteredData.filter(d => d.type === typeFilter);
    if (warehouseFilter) filteredData = filteredData.filter(d => d.warehouse === warehouseFilter || d.toWarehouse === warehouseFilter);
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const typeIcon = getTypeIcon(item.type);
        const warehouseInfo = item.type === 'transfer' ? `${item.warehouse} → ${item.toWarehouse}` : item.warehouse;
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${typeIcon}</td>
                <td>${warehouseInfo}</td>
                <td>${formatDate(item.date)}</td>
                <td>${item.employee}</td>
                <td>${item.totalQty}</td>
                <td>${item.note || 'N/A'}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon" onclick="printData(${item.id})"><i class="fas fa-print"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function getTypeIcon(type) {
    const icons = {
        'import': '<span class="badge badge-approved"><i class="fas fa-arrow-down"></i> Nhập kho</span>',
        'export': '<span class="badge badge-rejected"><i class="fas fa-arrow-up"></i> Xuất kho</span>',
        'transfer': '<span class="badge badge-pending"><i class="fas fa-exchange-alt"></i> Điều chuyển</span>'
    };
    return icons[type] || '';
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
    document.getElementById('filterType').addEventListener('change', function() {
        loadData(this.value, document.getElementById('filterWarehouse').value);
    });
    document.getElementById('filterWarehouse').addEventListener('change', function() {
        loadData(document.getElementById('filterType').value, this.value);
    });
}

function toggleTransferFields() {
    const type = document.getElementById('transactionType').value;
    const toWarehouseGroup = document.getElementById('toWarehouseGroup');
    
    if (type === 'transfer') {
        toWarehouseGroup.style.display = 'block';
        toWarehouseGroup.querySelector('select').required = true;
    } else {
        toWarehouseGroup.style.display = 'none';
        toWarehouseGroup.querySelector('select').required = false;
    }
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
                <input type="number" name="products[${productRowCount}][quantity]" min="1" value="1" required onchange="calculateTotalQty()">
            </div>
            <div class="form-group">
                <label>Đơn vị</label>
                <input type="text" name="products[${productRowCount}][unit]" value="Cái" required>
            </div>
            <div class="form-group">
                <label>Serial/Lô</label>
                <input type="text" name="products[${productRowCount}][serial]" placeholder="Nếu có">
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
    calculateTotalQty();
}

function calculateTotalQty() {
    let total = 0;
    document.querySelectorAll('.product-item').forEach(item => {
        const quantity = parseFloat(item.querySelector('[name*="[quantity]"]').value) || 0;
        total += quantity;
    });
    document.getElementById('totalQty').textContent = total;
}

function openAddModal(type) {
    editingId = null;
    productRowCount = 1;
    
    const titles = {
        'import': 'Phiếu nhập kho',
        'export': 'Phiếu xuất kho',
        'transfer': 'Phiếu điều chuyển'
    };
    
    document.getElementById('modalTitle').textContent = titles[type] || 'Phiếu kho';
    document.getElementById('dataForm').reset();
    setDefaultDate();
    
    document.getElementById('transactionType').value = type;
    toggleTransferFields();
    
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
                    <input type="number" name="products[0][quantity]" min="1" value="1" required onchange="calculateTotalQty()">
                </div>
                <div class="form-group">
                    <label>Đơn vị</label>
                    <input type="text" name="products[0][unit]" value="Cái" required>
                </div>
                <div class="form-group">
                    <label>Serial/Lô</label>
                    <input type="text" name="products[0][serial]" placeholder="Nếu có">
                </div>
            </div>
        </div>
    `;
    
    calculateTotalQty();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        let productList = item.products.map(p => `- ${p.name}: ${p.quantity} ${p.unit}${p.serial ? ' (Serial/Lô: ' + p.serial + ')' : ''}`).join('\n');
        const warehouseInfo = item.type === 'transfer' ? `${item.warehouse} → ${item.toWarehouse}` : item.warehouse;
        alert(`Thông tin phiếu:\n\nMã: ${item.code}\nLoại: ${getTypeText(item.type)}\nKho: ${warehouseInfo}\nNgày: ${formatDate(item.date)}\nNgười thực hiện: ${item.employee}\n\nSản phẩm:\n${productList}\n\nTổng SL: ${item.totalQty}\nGhi chú: ${item.note || 'Không có'}`);
    }
}

function getTypeText(type) {
    const texts = {
        'import': 'Nhập kho',
        'export': 'Xuất kho',
        'transfer': 'Điều chuyển'
    };
    return texts[type] || '';
}

function editData(id) {
    alert('Chức năng chỉnh sửa phiếu đang được phát triển');
}

function deleteData(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Bạn có chắc chắn muốn xóa phiếu "${item.code}"?`)) {
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
            unit: formData.get(`products[${index}][unit]`),
            serial: formData.get(`products[${index}][serial]`)
        });
    });
    
    const newData = {
        code: formData.get('code'),
        type: formData.get('type'),
        warehouse: formData.get('warehouse'),
        toWarehouse: formData.get('toWarehouse'),
        date: formData.get('date'),
        employee: formData.get('employee'),
        products: products,
        totalQty: parseInt(document.getElementById('totalQty').textContent),
        reference: formData.get('reference'),
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = data.findIndex(d => d.id === editingId);
        if (index !== -1) {
            data[index] = { ...data[index], ...newData };
            alert('Đã cập nhật phiếu!');
        }
    } else {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        data.push({ id: newId, ...newData });
        alert('Đã tạo phiếu mới!');
    }
    
    loadData();
    closeModal();
}

function saveAndPrint() {
    alert('Đã lưu và in phiếu!');
}

function printData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`In phiếu ${item.code}...`);
    }
}

function exportData() {
    alert('Chức năng xuất Excel đang được phát triển');
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
