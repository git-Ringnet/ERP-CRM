let orderData = [
    { id: 1, code: 'DH001', customer: 'Công ty TNHH ABC', revenue: 100000000, cost: 75000000, expense: 7000000, profit: 18000000, margin: 18 },
    { id: 2, code: 'DH002', customer: 'Công ty CP XYZ', revenue: 500000000, cost: 420000000, expense: 20000000, profit: 60000000, margin: 12 },
    { id: 3, code: 'DH003', customer: 'Công ty TNHH DEF', revenue: 75000000, cost: 60000000, expense: 3000000, profit: 12000000, margin: 16 }
];

let productData = [
    { id: 1, code: 'SP001', name: 'Laptop Dell XPS 15', avgPrice: 25000000, avgCost: 20000000, avgMargin: 20, quantitySold: 15, totalProfit: 75000000 },
    { id: 2, code: 'SP002', name: 'Màn hình LG 27 inch', avgPrice: 5500000, avgCost: 4500000, avgMargin: 18.2, quantitySold: 50, totalProfit: 50000000 },
    { id: 3, code: 'SP003', name: 'Bàn làm việc', avgPrice: 3000000, avgCost: 2200000, avgMargin: 26.7, quantitySold: 30, totalProfit: 24000000 }
];

let currentOrderId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadOrderData();
    loadProductData();
    setupSearch();
    setupFilter();
});

function loadOrderData(marginFilter = '') {
    const tbody = document.getElementById('orderTableBody');
    let filteredData = orderData;
    
    if (marginFilter) {
        filteredData = orderData.filter(d => {
            if (marginFilter === 'high') return d.margin > 15;
            if (marginFilter === 'medium') return d.margin >= 10 && d.margin <= 15;
            if (marginFilter === 'low') return d.margin < 10;
            return true;
        });
    }
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const marginClass = item.margin >= 15 ? 'text-success' : item.margin >= 10 ? 'text-warning' : 'text-danger';
        const marginBadge = item.margin >= 15 ? 'badge-approved' : item.margin >= 10 ? 'badge-pending' : 'badge-rejected';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.customer}</td>
                <td>${formatCurrency(item.revenue)}</td>
                <td>${formatCurrency(item.cost)}</td>
                <td>${formatCurrency(item.expense)}</td>
                <td class="${marginClass}">${formatCurrency(item.profit)}</td>
                <td><span class="badge ${marginBadge}">${item.margin}%</span></td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewOrderDetail(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon" onclick="openCalculateModal(${item.id})" title="Tính toán lại">
                        <i class="fas fa-calculator"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

function loadProductData() {
    const tbody = document.getElementById('productTableBody');
    tbody.innerHTML = '';
    
    productData.forEach(item => {
        const marginClass = item.avgMargin >= 20 ? 'text-success' : item.avgMargin >= 15 ? 'text-warning' : 'text-danger';
        const marginBadge = item.avgMargin >= 20 ? 'badge-approved' : item.avgMargin >= 15 ? 'badge-pending' : 'badge-rejected';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${formatCurrency(item.avgPrice)}</td>
                <td>${formatCurrency(item.avgCost)}</td>
                <td><span class="badge ${marginBadge}">${item.avgMargin.toFixed(1)}%</span></td>
                <td>${item.quantitySold}</td>
                <td class="${marginClass}">${formatCurrency(item.totalProfit)}</td>
            </tr>
        `;
    });
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#orderTableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
    
    document.getElementById('searchProductInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#productTableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
}

function setupFilter() {
    document.getElementById('filterMargin').addEventListener('change', function() {
        loadOrderData(this.value);
    });
}

function viewOrderDetail(id) {
    const item = orderData.find(d => d.id === id);
    if (item) {
        alert(`Chi tiết Margin đơn hàng:\n\nMã đơn: ${item.code}\nKhách hàng: ${item.customer}\n\nDoanh thu: ${formatCurrency(item.revenue)}\nGiá vốn: ${formatCurrency(item.cost)}\nChi phí BH: ${formatCurrency(item.expense)}\n\nLợi nhuận: ${formatCurrency(item.profit)}\nMargin: ${item.margin}%`);
    }
}

function openCalculateModal(id) {
    const item = orderData.find(d => d.id === id);
    if (item) {
        currentOrderId = id;
        const form = document.getElementById('calculateForm');
        
        form.elements['orderId'].value = item.id;
        form.elements['orderCode'].value = item.code;
        form.elements['revenue'].value = item.revenue;
        form.elements['cost'].value = item.cost;
        form.elements['expense'].value = item.expense;
        
        calculateMarginPreview();
        document.getElementById('calculateModal').style.display = 'block';
    }
}

function closeCalculateModal() {
    document.getElementById('calculateModal').style.display = 'none';
    currentOrderId = null;
}

function calculateMarginPreview() {
    const form = document.getElementById('calculateForm');
    const revenue = parseFloat(form.elements['revenue'].value) || 0;
    const cost = parseFloat(form.elements['cost'].value) || 0;
    const expense = parseFloat(form.elements['expense'].value) || 0;
    
    const profit = revenue - cost - expense;
    const margin = revenue > 0 ? ((profit / revenue) * 100).toFixed(2) : 0;
    
    document.getElementById('profitPreview').textContent = formatCurrency(profit);
    document.getElementById('marginPreview').textContent = margin + '%';
    
    // Change color based on margin
    const marginElement = document.getElementById('marginPreview');
    if (margin >= 15) {
        marginElement.style.color = '#27ae60';
    } else if (margin >= 10) {
        marginElement.style.color = '#f39c12';
    } else {
        marginElement.style.color = '#e74c3c';
    }
}

function recalculateMargin(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const revenue = parseFloat(formData.get('revenue'));
    const cost = parseFloat(formData.get('cost'));
    const expense = parseFloat(formData.get('expense'));
    const profit = revenue - cost - expense;
    const margin = ((profit / revenue) * 100).toFixed(2);
    
    const index = orderData.findIndex(d => d.id === currentOrderId);
    if (index !== -1) {
        orderData[index].revenue = revenue;
        orderData[index].cost = cost;
        orderData[index].expense = expense;
        orderData[index].profit = profit;
        orderData[index].margin = parseFloat(margin);
        
        loadOrderData();
        closeCalculateModal();
        alert('Đã cập nhật margin đơn hàng!');
    }
}

function openReportModal() {
    alert('Báo cáo Margin:\n\nTổng doanh thu: ' + formatCurrency(orderData.reduce((sum, o) => sum + o.revenue, 0)) + '\nTổng lợi nhuận: ' + formatCurrency(orderData.reduce((sum, o) => sum + o.profit, 0)) + '\nMargin trung bình: ' + (orderData.reduce((sum, o) => sum + o.margin, 0) / orderData.length).toFixed(2) + '%\n\nChức năng báo cáo chi tiết đang được phát triển...');
}

function exportData() {
    alert('Chức năng xuất Excel đang được phát triển');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

window.onclick = function(event) {
    if (event.target == document.getElementById('calculateModal')) {
        closeCalculateModal();
    }
}
