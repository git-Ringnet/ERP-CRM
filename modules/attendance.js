let data = [
    { id: 1, code: 'NV001', name: 'Nguyễn Văn A', department: 'Kinh doanh', workDays: 22, late: 2, earlyLeave: 1, overtime: 10, leave: 0 },
    { id: 2, code: 'NV002', name: 'Trần Thị B', department: 'Kế toán', workDays: 20, late: 0, earlyLeave: 0, overtime: 5, leave: 2 },
    { id: 3, code: 'NV003', name: 'Lê Văn C', department: 'Kho', workDays: 23, late: 1, earlyLeave: 0, overtime: 15, leave: 0 }
];

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const currentMonth = today.toISOString().slice(0, 7);
    document.getElementById('filterMonth').value = currentMonth;
    loadData();
    setupSearch();
    updateDashboard();
});

function updateDashboard() {
    document.getElementById('totalEmployees').textContent = data.length;
    document.getElementById('presentToday').textContent = data.length;
    const totalLate = data.reduce((sum, item) => sum + item.late, 0);
    document.getElementById('lateToday').textContent = totalLate;
    const totalOvertime = data.reduce((sum, item) => sum + item.overtime, 0);
    document.getElementById('totalOvertime').textContent = totalOvertime;
}

function loadData() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    data.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${item.department}</td>
                <td><strong>${item.workDays}</strong></td>
                <td class="${item.late > 0 ? 'text-warning' : ''}">${item.late}</td>
                <td class="${item.earlyLeave > 0 ? 'text-warning' : ''}">${item.earlyLeave}</td>
                <td class="text-success">${item.overtime}</td>
                <td>${item.leave}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewDetail(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `;
    });
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
        alert(`Chi tiết chấm công:\n\nMã NV: ${item.code}\nHọ tên: ${item.name}\nPhòng ban: ${item.department}\n\nSố ngày công: ${item.workDays}\nĐi muộn: ${item.late} lần\nVề sớm: ${item.earlyLeave} lần\nTăng ca: ${item.overtime} giờ\nNghỉ phép: ${item.leave} ngày`);
    }
}

function editData(id) {
    alert('Chỉnh sửa chấm công\n\nChức năng đang được phát triển...');
}

function openCheckInModal() {
    alert('Chấm công\n\nChức năng đang được phát triển...');
}

function exportData() {
    alert('Xuất báo cáo chấm công ra Excel');
}
