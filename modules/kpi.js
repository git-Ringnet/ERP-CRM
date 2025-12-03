let data = [
    { id: 1, department: 'Kinh doanh', employee: 'Nguyễn Văn A', kpiName: 'Doanh số bán hàng', target: 500000000, actual: 450000000, unit: 'VNĐ', achievement: 90, month: '2025-11', note: '' },
    { id: 2, department: 'Kinh doanh', employee: 'Trần Thị B', kpiName: 'Số đơn hàng mới', target: 20, actual: 25, unit: 'Đơn', achievement: 125, month: '2025-11', note: '' },
    { id: 3, department: 'Kho', employee: 'Lê Văn C', kpiName: 'Độ chính xác xuất nhập kho', target: 100, actual: 98, unit: '%', achievement: 98, month: '2025-11', note: '' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const currentMonth = today.toISOString().slice(0, 7);
    document.getElementById('filterMonth').value = currentMonth;
    loadData();
    setupSearch();
    updateDashboard();
});

function updateDashboard() {
    const totalKPI = data.length;
    const achievedKPI = data.filter(item => item.achievement >= 100).length;
    const notAchievedKPI = totalKPI - achievedKPI;
    const achievementRate = totalKPI > 0 ? ((achievedKPI / totalKPI) * 100).toFixed(1) : 0;
    
    document.getElementById('totalKPI').textContent = totalKPI;
    document.getElementById('achievedKPI').textContent = achievedKPI;
    document.getElementById('notAchievedKPI').textContent = notAchievedKPI;
    document.getElementById('achievementRate').textContent = achievementRate + '%';
}

function loadData() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    data.forEach(item => {
        const achievementBadge = getAchievementBadge(item.achievement);
        const achievementClass = item.achievement >= 100 ? 'text-success' : item.achievement >= 80 ? 'text-warning' : 'text-danger';
        
        tbody.innerHTML += `
            <tr>
                <td>${item.department}</td>
                <td><strong>${item.employee}</strong></td>
                <td>${item.kpiName}</td>
                <td>${formatNumber(item.target)} ${item.unit}</td>
                <td>${formatNumber(item.actual)} ${item.unit}</td>
                <td class="${achievementClass}"><strong>${item.achievement}%</strong></td>
                <td>${achievementBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function getAchievementBadge(achievement) {
    if (achievement >= 100) return '<span class="badge badge-approved">Đạt</span>';
    if (achievement >= 80) return '<span class="badge badge-pending">Gần đạt</span>';
    return '<span class="badge badge-rejected">Chưa đạt</span>';
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
    document.getElementById('modalTitle').textContent = 'Thiết lập KPI';
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
        alert(`Chi tiết KPI:\n\nPhòng ban: ${item.department}\nNhân viên: ${item.employee}\nChỉ tiêu: ${item.kpiName}\n\nMục tiêu: ${formatNumber(item.target)} ${item.unit}\nThực tế: ${formatNumber(item.actual)} ${item.unit}\nĐạt được: ${item.achievement}%\n\nĐánh giá: ${item.achievement >= 100 ? 'Đạt' : item.achievement >= 80 ? 'Gần đạt' : 'Chưa đạt'}`);
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa KPI';
        const form = document.getElementById('dataForm');
        Object.keys(item).forEach(key => {
            if (form.elements[key]) {
                form.elements[key].value = item[key] || '';
            }
        });
        document.getElementById('modal').style.display = 'block';
    }
}

function deleteData(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Bạn có chắc chắn muốn xóa KPI này?`)) {
        data = data.filter(d => d.id !== id);
        loadData();
        alert('Đã xóa thành công!');
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const target = parseFloat(formData.get('target'));
    const actual = parseFloat(formData.get('actual')) || 0;
    const achievement = target > 0 ? Math.round((actual / target) * 100) : 0;
    
    const newData = {
        department: formData.get('department'),
        employee: formData.get('employee'),
        kpiName: formData.get('kpiName'),
        target: target,
        actual: actual,
        unit: formData.get('unit'),
        achievement: achievement,
        month: formData.get('month'),
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = data.findIndex(d => d.id === editingId);
        if (index !== -1) {
            data[index] = { ...data[index], ...newData };
            alert('Đã cập nhật KPI!');
        }
    } else {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        data.push({ id: newId, ...newData });
        alert('Đã thêm KPI mới!');
    }
    
    loadData();
    updateDashboard();
    closeModal();
}

function calculateAchievement() {
    const form = document.getElementById('dataForm');
    const target = parseFloat(form.elements['target'].value) || 0;
    const actual = parseFloat(form.elements['actual'].value) || 0;
    const achievement = target > 0 ? Math.round((actual / target) * 100) : 0;
    console.log(`Achievement: ${achievement}%`);
}

function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) {
        closeModal();
    }
}
