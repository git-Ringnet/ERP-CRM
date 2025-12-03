let data = [
    { id: 1, code: 'NV001', name: 'Nguyễn Văn A', position: 'Nhân viên kinh doanh', department: 'Kinh doanh', email: 'nva@company.com', phone: '0934567890', status: 'active', birthDate: '1990-01-15', address: 'Hà Nội', joinDate: '2020-01-01', salary: 15000000, idCard: '001234567890', bankAccount: '1234567890', bankName: 'Vietcombank', note: '' },
    { id: 2, code: 'NV002', name: 'Trần Thị B', position: 'Trưởng phòng kinh doanh', department: 'Kinh doanh', email: 'ttb@company.com', phone: '0945678901', status: 'active', birthDate: '1988-05-20', address: 'TP.HCM', joinDate: '2018-03-15', salary: 25000000, idCard: '002345678901', bankAccount: '2345678901', bankName: 'Techcombank', note: '' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
    setupFilter();
});

function loadData(filter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    if (filter) {
        filteredData = data.filter(d => d.department === filter);
    }
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const statusBadge = getStatusBadge(item.status);
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${item.position}</td>
                <td>${item.department}</td>
                <td>${item.email}</td>
                <td>${item.phone}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function getStatusBadge(status) {
    switch(status) {
        case 'active': return '<span class="badge badge-approved">Đang làm việc</span>';
        case 'leave': return '<span class="badge badge-pending">Nghỉ phép</span>';
        case 'resigned': return '<span class="badge badge-rejected">Đã nghỉ việc</span>';
        default: return '';
    }
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#tableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
}

function setupFilter() {
    document.getElementById('filterDept').addEventListener('change', function() {
        loadData(this.value);
    });
}

function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm nhân viên mới';
    document.getElementById('dataForm').reset();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Thông tin nhân viên:\n\nMã: ${item.code}\nHọ tên: ${item.name}\nChức vụ: ${item.position}\nPhòng ban: ${item.department}\nEmail: ${item.email}\nĐiện thoại: ${item.phone}\nLương: ${formatCurrency(item.salary)}\nTrạng thái: ${getStatusText(item.status)}`);
    }
}

function getStatusText(status) {
    switch(status) {
        case 'active': return 'Đang làm việc';
        case 'leave': return 'Nghỉ phép';
        case 'resigned': return 'Đã nghỉ việc';
        default: return '';
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa nhân viên';
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
    if (item && confirm(`Bạn có chắc chắn muốn xóa nhân viên "${item.name}"?`)) {
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
        position: formData.get('position'),
        department: formData.get('department'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        status: formData.get('status'),
        birthDate: formData.get('birthDate'),
        address: formData.get('address'),
        joinDate: formData.get('joinDate'),
        salary: parseInt(formData.get('salary')) || 0,
        idCard: formData.get('idCard'),
        bankAccount: formData.get('bankAccount'),
        bankName: formData.get('bankName'),
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

function exportData() {
    alert('Chức năng xuất Excel đang được phát triển');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) {
        closeModal();
    }
}
