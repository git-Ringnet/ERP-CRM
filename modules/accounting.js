let ordersData = [
    { id: 1, code: 'DH001', type: 'sales', partner: 'Công ty TNHH ABC', date: '2025-11-09', total: 100000000, paid: 50000000, remaining: 50000000, paymentStatus: 'partial' },
    { id: 2, code: 'PO001', type: 'purchase', partner: 'Nhà cung cấp A', date: '2025-11-09', total: 200000000, paid: 200000000, remaining: 0, paymentStatus: 'paid' }
];

let paymentData = [
    { id: 1, partner: 'Công ty TNHH ABC', type: 'customer', totalAmount: 200000000, paidAmount: 150000000, remaining: 50000000, overdue: 0 },
    { id: 2, partner: 'Nhà cung cấp A', type: 'supplier', totalAmount: 300000000, paidAmount: 250000000, remaining: 50000000, overdue: 20000000 }
];

let currencyData = [
    { id: 1, name: 'Đô la Mỹ', symbol: 'USD', rate: 24000, updateDate: '2025-11-09' },
    { id: 2, name: 'Euro', symbol: 'EUR', rate: 26000, updateDate: '2025-11-09' },
    { id: 3, name: 'Yên Nhật', symbol: 'JPY', rate: 160, updateDate: '2025-11-09' }
];

document.addEventListener('DOMContentLoaded', function() {
    loadOrdersData();
    loadPaymentData();
    loadCurrencyData();
    updateFinancialDashboard();
});

function updateFinancialDashboard() {
    const totalRevenue = 1500000000;
    const totalCost = 900000000;
    const totalProfit = totalRevenue - totalCost;
    const profitMargin = ((totalProfit / totalRevenue) * 100).toFixed(1);
    
    document.getElementById('totalRevenue').textContent = formatCurrency(totalRevenue);
    document.getElementById('totalCost').textContent = formatCurrency(totalCost);
    document.getElementById('totalProfit').textContent = formatCurrency(totalProfit);
    document.getElementById('profitMargin').textContent = profitMargin + '%';
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

function loadOrdersData() {
    const tbody = document.getElementById('ordersTableBody');
    tbody.innerHTML = '';
    
    ordersData.forEach(item => {
        const typeBadge = item.type === 'sales' ? '<span class="badge badge-approved">Đơn bán</span>' : '<span class="badge badge-pending">Đơn mua</span>';
        const paymentBadge = getPaymentBadge(item.paymentStatus);
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${typeBadge}</td>
                <td>${item.partner}</td>
                <td>${formatDate(item.date)}</td>
                <td>${formatCurrency(item.total)}</td>
                <td>${formatCurrency(item.paid)}</td>
                <td class="${item.remaining > 0 ? 'text-warning' : 'text-success'}">${formatCurrency(item.remaining)}</td>
                <td>${paymentBadge}</td>
            </tr>
        `;
    });
}

function loadPaymentData() {
    const tbody = document.getElementById('paymentTableBody');
    tbody.innerHTML = '';
    
    paymentData.forEach(item => {
        const typeBadge = item.type === 'customer' ? '<span class="badge badge-approved">Khách hàng</span>' : '<span class="badge badge-pending">Nhà cung cấp</span>';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.partner}</strong></td>
                <td>${typeBadge}</td>
                <td>${formatCurrency(item.totalAmount)}</td>
                <td>${formatCurrency(item.paidAmount)}</td>
                <td class="text-warning">${formatCurrency(item.remaining)}</td>
                <td class="${item.overdue > 0 ? 'text-danger' : ''}">${formatCurrency(item.overdue)}</td>
                <td>
                    <button class="btn-icon btn-view"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon"><i class="fas fa-money-bill"></i></button>
                </td>
            </tr>
        `;
    });
}

function loadCurrencyData() {
    const tbody = document.getElementById('currencyTableBody');
    tbody.innerHTML = '';
    
    currencyData.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.name}</strong></td>
                <td>${item.symbol}</td>
                <td>${formatNumber(item.rate)} VNĐ</td>
                <td>${formatDate(item.updateDate)}</td>
                <td>
                    <button class="btn-icon btn-edit"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon"><i class="fas fa-history"></i></button>
                </td>
            </tr>
        `;
    });
}

function getPaymentBadge(status) {
    const badges = {
        'paid': '<span class="badge badge-approved">Đã thanh toán</span>',
        'partial': '<span class="badge badge-pending">Thanh toán 1 phần</span>',
        'unpaid': '<span class="badge badge-rejected">Chưa thanh toán</span>'
    };
    return badges[status] || '';
}

function viewReport(type) {
    const reports = {
        'income': 'Báo cáo lãi lỗ',
        'balance': 'Bảng cân đối kế toán',
        'cashflow': 'Báo cáo dòng tiền'
    };
    alert(`Xem ${reports[type]}\n\nChức năng đang được phát triển...`);
}

function exportMisa() {
    alert('Xuất file dữ liệu chứng từ phù hợp với Misa\n\nĐang tạo file theo template chuẩn thống nhất của Misa...');
}

function openAddCurrencyModal() {
    alert('Thêm/Cập nhật tỷ giá\n\nChức năng đang được phát triển...');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN');
}
