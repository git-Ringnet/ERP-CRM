// Sample data
let products = [
    { id: 1, code: 'SP001', name: 'Laptop Dell XPS 15', category: 'electronics', unit: 'Cái', price: 25000000, cost: 20000000, stock: 50, minStock: 10, maxStock: 100, managementType: 'serial', serialPrefix: 'SN-', autoGenerateSerial: true, description: 'Laptop cao cấp', note: '' },
    { id: 2, code: 'SP002', name: 'Màn hình LG 27 inch', category: 'electronics', unit: 'Cái', price: 5500000, cost: 4500000, stock: 120, minStock: 20, maxStock: 200, managementType: 'lot', expiryMonths: 24, trackExpiry: true, description: 'Màn hình văn phòng', note: '' },
    { id: 3, code: 'SP003', name: 'Bàn làm việc', category: 'furniture', unit: 'Cái', price: 3000000, cost: 2200000, stock: 30, minStock: 5, maxStock: 50, managementType: 'normal', description: 'Bàn gỗ công nghiệp', note: '' }
];

let editingId = null;

// Load products on page load
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    setupSearch();
    setupFilter();
});

// Load products to table
function loadProducts(filter = '') {
    const tbody = document.getElementById('productTableBody');
    let filteredProducts = products;

    if (filter) {
        filteredProducts = products.filter(p => p.managementType === filter);
    }

    tbody.innerHTML = '';
    filteredProducts.forEach(product => {
        const managementBadge = getManagementBadge(product.managementType);
        const row = `
            <tr>
                <td><strong>${product.code}</strong></td>
                <td>${product.name}</td>
                <td>${product.unit}</td>
                <td>${formatCurrency(product.price)}</td>
                <td>${formatCurrency(product.cost)}</td>
                <td>${product.stock}</td>
                <td>${managementBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewProduct(${product.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon btn-edit" onclick="editProduct(${product.id})" title="Sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteProduct(${product.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function getManagementBadge(type) {
    switch(type) {
        case 'serial':
            return '<span class="badge badge-serial">Serial Number</span>';
        case 'lot':
            return '<span class="badge badge-lot">Số lô</span>';
        default:
            return '<span class="badge badge-normal">Thông thường</span>';
    }
}

// Setup search
function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#productTableBody tr');
        
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
        loadProducts(this.value);
    });
}

// Toggle management fields
function toggleManagementFields() {
    const type = document.getElementById('productManagementType').value;
    document.getElementById('serialFields').style.display = type === 'serial' ? 'block' : 'none';
    document.getElementById('lotFields').style.display = type === 'lot' ? 'block' : 'none';
}

// Open add product modal
function openAddProductModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm sản phẩm mới';
    document.getElementById('productForm').reset();
    toggleManagementFields();
    document.getElementById('productModal').style.display = 'block';
}

// Close modal
function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
    editingId = null;
}

// View product
function viewProduct(id) {
    const product = products.find(p => p.id === id);
    if (product) {
        const margin = ((product.price - product.cost) / product.price * 100).toFixed(2);
        alert(`Thông tin sản phẩm:\n\nMã: ${product.code}\nTên: ${product.name}\nĐơn vị: ${product.unit}\nGiá bán: ${formatCurrency(product.price)}\nGiá vốn: ${formatCurrency(product.cost)}\nMargin: ${margin}%\nTồn kho: ${product.stock}\nLoại quản lý: ${getManagementType(product.managementType)}\nMô tả: ${product.description || 'Không có'}`);
    }
}

function getManagementType(type) {
    switch(type) {
        case 'serial': return 'Theo Serial Number';
        case 'lot': return 'Theo Số lô';
        default: return 'Thông thường';
    }
}

// Edit product
function editProduct(id) {
    const product = products.find(p => p.id === id);
    if (product) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa sản phẩm';
        
        document.getElementById('productId').value = product.id;
        document.getElementById('productCode').value = product.code;
        document.getElementById('productName').value = product.name;
        document.getElementById('productCategory').value = product.category || '';
        document.getElementById('productUnit').value = product.unit;
        document.getElementById('productPrice').value = product.price;
        document.getElementById('productCost').value = product.cost;
        document.getElementById('productMinStock').value = product.minStock;
        document.getElementById('productMaxStock').value = product.maxStock;
        document.getElementById('productManagementType').value = product.managementType;
        document.getElementById('productDescription').value = product.description || '';
        document.getElementById('productNote').value = product.note || '';
        
        if (product.managementType === 'serial') {
            document.getElementById('autoGenerateSerial').checked = product.autoGenerateSerial || false;
            document.getElementById('serialPrefix').value = product.serialPrefix || '';
        }
        
        if (product.managementType === 'lot') {
            document.getElementById('expiryMonths').value = product.expiryMonths || '';
            document.getElementById('trackExpiry').checked = product.trackExpiry || false;
        }
        
        toggleManagementFields();
        document.getElementById('productModal').style.display = 'block';
    }
}

// Delete product
function deleteProduct(id) {
    const product = products.find(p => p.id === id);
    if (product && confirm(`Bạn có chắc chắn muốn xóa sản phẩm "${product.name}"?`)) {
        products = products.filter(p => p.id !== id);
        loadProducts();
        alert('Đã xóa sản phẩm thành công!');
    }
}

// Save product
function saveProduct(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        code: formData.get('code'),
        name: formData.get('name'),
        category: formData.get('category'),
        unit: formData.get('unit'),
        price: parseInt(formData.get('price')) || 0,
        cost: parseInt(formData.get('cost')) || 0,
        stock: 0,
        minStock: parseInt(formData.get('minStock')) || 0,
        maxStock: parseInt(formData.get('maxStock')) || 0,
        managementType: formData.get('managementType'),
        description: formData.get('description'),
        note: formData.get('note')
    };
    
    if (data.managementType === 'serial') {
        data.autoGenerateSerial = formData.get('autoGenerateSerial') === 'on';
        data.serialPrefix = formData.get('serialPrefix');
    }
    
    if (data.managementType === 'lot') {
        data.expiryMonths = parseInt(formData.get('expiryMonths')) || 0;
        data.trackExpiry = formData.get('trackExpiry') === 'on';
    }
    
    if (editingId) {
        const index = products.findIndex(p => p.id === editingId);
        if (index !== -1) {
            products[index] = { ...products[index], ...data };
            alert('Đã cập nhật thông tin sản phẩm!');
        }
    } else {
        const newId = Math.max(...products.map(p => p.id), 0) + 1;
        products.push({ id: newId, ...data });
        alert('Đã thêm sản phẩm mới!');
    }
    
    loadProducts();
    closeProductModal();
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
    const modal = document.getElementById('productModal');
    if (event.target == modal) {
        closeProductModal();
    }
}
