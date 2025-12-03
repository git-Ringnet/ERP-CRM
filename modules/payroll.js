let data = [
    { id: 1, code: 'NV001', name: 'Nguyễn Văn A', baseSalary: 15000000, allowance: 2000000, bonus: 3000000, overtime: 2000000, deduction: 1000000, netSalary: 21000000, status: 'approved' },
    { id: 2, code: 'NV002', name: 'Trần Thị B', baseSalary: 25000000, allowance: 3000000, bonus: 5000000, overtime: 1000000, deduction: 2000000, netSalary: 32000000, status: 'paid' },
    { id: 3, code: 'NV003', name: 'Lê Văn C', baseSalary: 12000000, allowance: 1500000, bonus: 2000000, overtime: 3000000, deduction: 500000, netSalary: 18000000, status: 'pending' }
];

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const currentMonth = today.toISOString().slice(0, 7);
    document.getElementById('filterMonth').value = currentMonth;
    loadData();
    setupSearch();
    updateSummary();
});

function loadData() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    data.forEach(item => {
        const statusBadge = getStatusBadge(item.status);
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${formatCurrency(item.baseSalary)}</td>
                <td>${formatCurrency(item.allowance)}</td>
                <td class="text-success">${formatCurrency(item.bonus)}</td>
                <td class="text-success">${formatCurrency(item.overtime)}</td>
                <td class="text-danger">${formatCurrency(item.deduction)}</td>
                <td><strong>${formatCurrency(item.netSalary)}</strong></td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewDetail(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon" onclick="printPayslip(${item.id})"><i class="fas fa-print"></i></button>
                </td>
            </tr>
        `;
    });
    
    updateSummary();
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge badge-pending">Chưa chốt</span>',
        'approved': '<span class="badge badge-approved">Đã chốt</span>',
        'paid': '<span class="badge badge-approved">Đã thanh toán</span>'
    };
    return badges[status] || '';
}

function updateSummary() {
    const total = data.reduce((sum, item) => sum + item.netSalary, 0);
    const paid = data.filter(item => item.status === 'paid').reduce((sum, item) => sum + item.netSalary, 0);
    const remaining = total - paid;
    
    document.getElementById('totalSalary').textContent = formatCurrency(total);
    document.getElementById('paidSalary').textContent = formatCurrency(paid);
    document.getElementById('remainingSalary').textContent = formatCurrency(remaining);
    
    // Update dashboard
    const totalBase = data.reduce((sum, item) => sum + item.baseSalary, 0);
    const totalBonus = data.reduce((sum, item) => sum + item.bonus, 0);
    const avgSalary = data.length > 0 ? total / data.length : 0;
    
    document.getElementById('totalEmp').textContent = data.length;
    document.getElementById('totalBase').textContent = formatCurrency(totalBase);
    document.getElementById('totalBonus').textContent = formatCurrency(totalBonus);
    document.getElementById('avgSalary').textContent = formatCurrency(avgSalary);
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#tableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
}

function viewDetail(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Bảng lương chi tiết:\n\nMã NV: ${item.code}\nHọ tên: ${item.name}\n\nLương cơ bản: ${formatCurrency(item.baseSalary)}\nPhụ cấp: ${formatCurrency(item.allowance)}\nThưởng: ${formatCurrency(item.bonus)}\nTăng ca: ${formatCurrency(item.overtime)}\nKhấu trừ: ${formatCurrency(item.deduction)}\n\nThực lĩnh: ${formatCurrency(item.netSalary)}`);
    }
}

function calculateSalary() {
    alert('Tính lương tháng này\n\nHệ thống sẽ tự động tính lương dựa trên:\n- Chấm công\n- Phụ cấp\n- Thưởng\n- Tăng ca\n- Khấu trừ\n\nChức năng đang được phát triển...');
}

function printPayslip(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`In phiếu lương cho ${item.name}\n\nChức năng đang được phát triển...`);
    }
}

function exportData() {
    alert('Xuất bảng lương ra Excel');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}
