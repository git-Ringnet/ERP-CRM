let data = [
    { id: 1, code: 'NV001', employee: 'Nguyễn Văn A', target: 500000000, actual: 450000000, achievement: 90, commissionRate: 5, commission: 22500000, month: '2025-11', status: 'active' },
    { id: 2, code: 'NV002', employee: 'Trần Thị B', target: 800000000, actual: 900000000, achievement: 112.5, commissionRate: 5, commission: 45000000, month: '2025-11', status: 'active' },
    { id: 3, code: 'NV003', employee: 'Lê Văn C', target: 300000000, actual: 250000000, achievement: 83.3, commissionRate: 5, commission: 12500000, month: '2025-11', status: 'active' }
];

let editingId = null;

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
        const achievementClass = item.achievement >= 100 ? 'text-success' : item.achievement >= 80 ? 'text-warning' : 'text-danger';
        const statusBadge = item.achievement >= 100 ? '<span class="badge badge-approved">Đạt</span>' : '<span class="badge badge-pending">Chưa đạt</span>';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.employee}</td>
                <td>${formatCurrency(item.target)}</td>
                <td>${formatCurrency(item.actual)}</td>
                <td class="${achievementClass}"><strong>${item.achievement.toFixed(1)}%</strong></td>
                <td>${item.commissionRate}%</td>
                <td class="text-success"><strong>${formatCurrency(item.commission)}</strong></td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `;
    });
    
    updateSummary();
}

function updateSummary() {
    const totalTarget = data.reduce((sum, item) => sum + item.target, 0);
    const totalActual = data.reduce((sum, item) => sum + item.actual, 0);
    const totalAchievement = totalTarget > 0 ? ((totalActual / totalTarget) * 100).toFixed(1) : 0;
    const totalCommission = data.reduce((sum, item) => sum + item.commission, 0);
    
    document.getElementById('totalTarget').textContent = formatCurrency(totalTarget);
    document.getElementById('totalActual').textContent = formatCurrency(totalActual);
    document.getElementById('totalAchievement').textContent = totalAchievement + '%';
    document.getElementById('totalCommission').textContent = formatCurrency(totalCommission);
    
    // Update dashboard
    const totalSales = data.length;
    const achievedTarget = data.filter(item => item.achievement >= 100).length;
    const completionRate = totalSales > 0 ? ((achievedTarget / totalSales) * 100).toFixed(1) : 0;
    const topPerformer = data.length > 0 ? data.reduce((max, item) => item.achievement > max.achievement ? item : max).employee : '-';
    
    document.getElementById('totalSales').textContent = totalSales;
    document.getElementById('achievedTarget').textContent = achievedTarget;
    document.getElementById('completionRate').textContent = completionRate + '%';
    document.getElementById('topPerformer').textContent = topPerformer;
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#tableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
}

function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thiết lập mục tiêu doanh số';
    document.getElementById('dataForm').reset();
    const today = new Date();
    document.querySelector('[name="month"]').value = today.toISOString().slice(0, 7);
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Chi tiết doanh số:\n\nNhân viên: ${item.employee}\n\nMục tiêu: ${formatCurrency(item.target)}\nThực tế: ${formatCurrency(item.actual)}\nĐạt được: ${item.achievement.toFixed(1)}%\n\nTỷ lệ hoa hồng: ${item.commissionRate}%\nTiền hoa hồng: ${formatCurrency(item.commission)}`);
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa mục tiêu doanh số';
        const form = document.getElementById('dataForm');
        form.elements['employee'].value = item.employee;
        form.elements['target'].value = item.target;
        form.elements['actual'].value = item.actual;
        form.elements['commissionRate'].value = item.commissionRate;
        form.elements['month'].value = item.month;
        form.elements['note'].value = item.note || '';
        document.getElementById('modal').style.display = 'block';
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const target = parseFloat(formData.get('target'));
    const actual = parseFloat(formData.get('actual')) || 0;
    const commissionRate = parseFloat(formData.get('commissionRate'));
    const achievement = target > 0 ? (actual / target) * 100 : 0;
    const commission = (actual * commissionRate) / 100;
    
    const newData = {
        code: 'NV' + String(data.length + 1).padStart(3, '0'),
        employee: formData.get('employee'),
        target: target,
        actual: actual,
        achievement: achievement,
        commissionRate: commissionRate,
        commission: commission,
        month: formData.get('month'),
        status: 'active',
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = data.findIndex(d => d.id === editingId);
        if (index !== -1) {
            data[index] = { ...data[index], ...newData };
            alert('Đã cập nhật!');
        }
    } else {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        data.push({ id: newId, ...newData });
        alert('Đã thêm mới!');
    }
    
    loadData();
    closeModal();
}

function calculateCommission() {
    const form = document.getElementById('dataForm');
    const actual = parseFloat(form.elements['actual'].value) || 0;
    const commissionRate = parseFloat(form.elements['commissionRate'].value) || 0;
    const commission = (actual * commissionRate) / 100;
    console.log(`Commission: ${formatCurrency(commission)}`);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) {
        closeModal();
    }
}
