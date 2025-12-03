let data = [
    { id: 1, code: 'DA001', name: 'Dự án cung cấp thiết bị văn phòng', customer: 'Công ty TNHH ABC', startDate: '2025-01-01', endDate: '2025-06-30', value: 500000000, progress: 65, status: 'in-progress', manager: 'Nguyễn Văn A', description: 'Cung cấp toàn bộ thiết bị văn phòng cho chi nhánh mới', note: '' },
    { id: 2, code: 'DA002', name: 'Dự án hệ thống máy tính', customer: 'Công ty CP XYZ', startDate: '2025-03-01', endDate: '2025-12-31', value: 1000000000, progress: 30, status: 'in-progress', manager: 'Trần Thị B', description: 'Triển khai hệ thống máy tính cho toàn công ty', note: '' },
    { id: 3, code: 'DA003', name: 'Dự án nội thất văn phòng', customer: 'Công ty TNHH DEF', startDate: '2024-10-01', endDate: '2024-12-31', value: 300000000, progress: 100, status: 'completed', manager: 'Nguyễn Văn A', description: 'Cung cấp nội thất cho văn phòng mới', note: 'Hoàn thành đúng tiến độ' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
});

function loadData() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    data.forEach(item => {
        const statusBadge = getStatusBadge(item.status);
        const progressBar = `
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${item.progress}%">${item.progress}%</div>
            </div>
        `;
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${item.customer}</td>
                <td>${formatDate(item.startDate)}</td>
                <td>${formatDate(item.endDate)}</td>
                <td><strong>${formatCurrency(item.value)}</strong></td>
                <td>${progressBar}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon" onclick="viewOrders(${item.id})" title="Xem đơn hàng"><i class="fas fa-file-invoice"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function getStatusBadge(status) {
    const badges = {
        'planning': '<span class="badge badge-pending">Lên kế hoạch</span>',
        'in-progress': '<span class="badge" style="background: #cfe2ff; color: #084298;">Đang thực hiện</span>',
        'completed': '<span class="badge badge-approved">Hoàn thành</span>',
        'cancelled': '<span class="badge badge-rejected">Đã hủy</span>'
    };
    return badges[status] || '';
}

function getStatusText(status) {
    const texts = {
        'planning': 'Lên kế hoạch',
        'in-progress': 'Đang thực hiện',
        'completed': 'Hoàn thành',
        'cancelled': 'Đã hủy'
    };
    return texts[status] || '';
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#tableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
}

function filterData() {
    const filterStatus = document.getElementById('filterStatus').value;
    document.querySelectorAll('#tableBody tr').forEach(row => {
        if (!filterStatus) {
            row.style.display = '';
        } else {
            const status = row.cells[7].textContent.toLowerCase();
            row.style.display = status.includes(getStatusText(filterStatus).toLowerCase()) ? '' : 'none';
        }
    });
}

function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm dự án mới';
    document.getElementById('dataForm').reset();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Chi tiết dự án:\n\nMã: ${item.code}\nTên: ${item.name}\nKhách hàng: ${item.customer}\n\nNgày bắt đầu: ${formatDate(item.startDate)}\nNgày kết thúc: ${formatDate(item.endDate)}\nGiá trị: ${formatCurrency(item.value)}\n\nTiến độ: ${item.progress}%\nTrạng thái: ${getStatusText(item.status)}\nNgười quản lý: ${item.manager}\n\nMô tả: ${item.description || 'Không có'}\nGhi chú: ${item.note || 'Không có'}`);
    }
}

function viewOrders(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Danh sách đơn hàng của dự án: ${item.name}\n\nChức năng đang được phát triển...`);
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa dự án';
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
    if (item && confirm(`Bạn có chắc chắn muốn xóa dự án "${item.name}"?`)) {
        data = data.filter(d => d.id !== id);
        loadData();
        alert('Đã xóa thành công!');
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const newData = {
        code: formData.get('code'),
        name: formData.get('name'),
        customer: formData.get('customer'),
        startDate: formData.get('startDate'),
        endDate: formData.get('endDate'),
        value: parseFloat(formData.get('value')),
        progress: parseInt(formData.get('progress')) || 0,
        status: formData.get('status'),
        manager: formData.get('manager'),
        description: formData.get('description'),
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
