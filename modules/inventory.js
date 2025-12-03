let data = [
    { id: 1, code: 'SP001', name: 'Laptop Dell XPS 15', warehouse: 'Kho chính', stock: 45, minStock: 10, avgCost: 20000000, expiryDate: null, warrantyMonths: 24 },
    { id: 2, code: 'SP002', name: 'Màn hình LG 27 inch', warehouse: 'Kho chính', stock: 8, minStock: 20, avgCost: 4500000, expiryDate: '2026-11-01', warrantyMonths: 12 },
    { id: 3, code: 'SP003', name: 'Bàn làm việc', warehouse: 'Kho phụ', stock: 0, minStock: 5, avgCost: 2200000, expiryDate: null, warrantyMonths: 0 },
    { id: 4, code: 'SP001', name: 'Laptop Dell XPS 15', warehouse: 'Kho phụ', stock: 5, minStock: 10, avgCost: 20000000, expiryDate: null, warrantyMonths: 24 },
    { id: 5, code: 'SP004', name: 'Ghế văn phòng', warehouse: 'Kho chính', stock: 15, minStock: 10, avgCost: 1500000, expiryDate: '2025-12-01', warrantyMonths: 6 }
];

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
    setupFilters();
    updateSummary();
});

function loadData(warehouseFilter = '', statusFilter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    
    if (warehouseFilter) filteredData = filteredData.filter(d => d.warehouse === warehouseFilter);
    if (statusFilter) filteredData = filteredData.filter(d => getStatus(d) === statusFilter);
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const status = getStatus(item);
        const statusBadge = getStatusBadge(status);
        const stockValue = item.stock * item.avgCost;
        const expiryInfo = item.expiryDate ? formatDate(item.expiryDate) : 'N/A';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${item.warehouse}</td>
                <td><strong>${item.stock}</strong></td>
                <td>${item.minStock}</td>
                <td>${formatCurrency(item.avgCost)}</td>
                <td>${formatCurrency(stockValue)}</td>
                <td>${expiryInfo}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewHistory(${item.id})"><i class="fas fa-history"></i></button>
                    <button class="btn-icon" onclick="adjustStock(${item.id})"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `;
    });
    
    updateSummary();
}

function getStatus(item) {
    if (item.stock === 0) return 'out';
    if (item.stock < item.minStock) return 'low';
    
    if (item.expiryDate) {
        const today = new Date();
        const expiry = new Date(item.expiryDate);
        const daysUntilExpiry = Math.floor((expiry - today) / (1000 * 60 * 60 * 24));
        if (daysUntilExpiry < 30 && daysUntilExpiry > 0) return 'expiring';
    }
    
    return 'normal';
}

function getStatusBadge(status) {
    const badges = {
        'normal': '<span class="badge badge-approved">Bình thường</span>',
        'low': '<span class="badge badge-pending">Sắp hết</span>',
        'out': '<span class="badge badge-rejected">Hết hàng</span>',
        'expiring': '<span class="badge badge-pending">Sắp hết hạn</span>'
    };
    return badges[status] || '';
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#tableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
        updateSummary();
    });
}

function setupFilters() {
    document.getElementById('filterWarehouse').addEventListener('change', function() {
        loadData(this.value, document.getElementById('filterStatus').value);
    });
    document.getElementById('filterStatus').addEventListener('change', function() {
        loadData(document.getElementById('filterWarehouse').value, this.value);
    });
}

function updateSummary() {
    const visibleRows = Array.from(document.querySelectorAll('#tableBody tr')).filter(row => row.style.display !== 'none');
    
    let totalValue = 0;
    let lowStock = 0;
    let outOfStock = 0;
    
    visibleRows.forEach(row => {
        const cells = row.cells;
        const stock = parseInt(cells[3].textContent);
        const minStock = parseInt(cells[4].textContent);
        const value = parseFloat(cells[6].textContent.replace(/[^\d]/g, ''));
        
        totalValue += value;
        if (stock === 0) outOfStock++;
        else if (stock < minStock) lowStock++;
    });
    
    document.getElementById('totalValue').textContent = formatCurrency(totalValue);
    document.getElementById('totalProducts').textContent = visibleRows.length;
    document.getElementById('lowStock').textContent = lowStock;
    document.getElementById('outOfStock').textContent = outOfStock;
}

function viewHistory(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Lịch sử xuất nhập kho:\n\nSản phẩm: ${item.name}\nKho: ${item.warehouse}\nTồn hiện tại: ${item.stock}\n\nChức năng đang được phát triển...`);
    }
}

function adjustStock(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        const newStock = prompt(`Điều chỉnh tồn kho cho ${item.name} tại ${item.warehouse}\nTồn hiện tại: ${item.stock}\n\nNhập số lượng mới:`, item.stock);
        if (newStock !== null && !isNaN(newStock)) {
            const index = data.findIndex(d => d.id === id);
            if (index !== -1) {
                data[index].stock = parseInt(newStock);
                loadData();
                alert('Đã cập nhật tồn kho!');
            }
        }
    }
}

function openInventoryCheckModal() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('[name="checkDate"]').value = today;
    document.getElementById('inventoryCheckModal').style.display = 'block';
}

function closeInventoryCheckModal() {
    document.getElementById('inventoryCheckModal').style.display = 'none';
}

function saveInventoryCheck(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    alert(`Bắt đầu kiểm kê:\n\nKho: ${formData.get('warehouse')}\nNgày: ${formatDate(formData.get('checkDate'))}\nNgười kiểm: ${formData.get('checker')}\n\nChức năng đang được phát triển...`);
    
    closeInventoryCheckModal();
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
    if (event.target == document.getElementById('inventoryCheckModal')) {
        closeInventoryCheckModal();
    }
}
