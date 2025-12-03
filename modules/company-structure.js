let departments = [
    { id: 1, code: 'PB001', name: 'Phòng Kinh doanh', manager: 'Lê Văn C', employees: 5, email: 'kinhdoanh@company.com', phone: '0901234567', parentDept: 'Ban Giám đốc', description: 'Phụ trách bán hàng và chăm sóc khách hàng' },
    { id: 2, code: 'PB002', name: 'Phòng Kế toán', manager: 'Phạm Thị D', employees: 3, email: 'ketoan@company.com', phone: '0912345678', parentDept: 'Ban Giám đốc', description: 'Phụ trách kế toán và tài chính' },
    { id: 3, code: 'PB003', name: 'Phòng Kho', manager: 'Hoàng Văn E', employees: 4, email: 'kho@company.com', phone: '0923456789', parentDept: 'Ban Giám đốc', description: 'Quản lý kho hàng và xuất nhập' },
    { id: 4, code: 'PB004', name: 'Phòng Hành chính', manager: 'Vũ Thị F', employees: 2, email: 'hanhchinh@company.com', phone: '0934567890', parentDept: 'Ban Giám đốc', description: 'Hành chính nhân sự' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
});

function loadData() {
    const tbody = document.getElementById('departmentTableBody');
    tbody.innerHTML = '';
    
    departments.forEach(dept => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${dept.code}</strong></td>
                <td>${dept.name}</td>
                <td>${dept.manager || 'Chưa có'}</td>
                <td><span class="badge badge-normal">${dept.employees} người</span></td>
                <td>${dept.email || 'N/A'}</td>
                <td>${dept.phone || 'N/A'}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewDepartment('${dept.code}')"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${dept.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${dept.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function viewDepartment(deptCode) {
    const dept = departments.find(d => d.code === deptCode);
    if (dept) {
        alert(`Thông tin phòng ban:\n\nMã: ${dept.code}\nTên: ${dept.name}\nTrưởng phòng: ${dept.manager || 'Chưa có'}\nSố nhân viên: ${dept.employees}\nEmail: ${dept.email || 'N/A'}\nSĐT: ${dept.phone || 'N/A'}\nCấp trên: ${dept.parentDept || 'Không có'}\n\nMô tả: ${dept.description || 'Không có'}`);
    } else {
        alert('Xem chi tiết phòng ban: ' + deptCode);
    }
}

function openAddDepartmentModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm phòng ban';
    document.getElementById('dataForm').reset();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function editData(id) {
    const dept = departments.find(d => d.id === id);
    if (dept) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa phòng ban';
        const form = document.getElementById('dataForm');
        Object.keys(dept).forEach(key => {
            if (form.elements[key]) {
                form.elements[key].value = dept[key] || '';
            }
        });
        document.getElementById('modal').style.display = 'block';
    }
}

function deleteData(id) {
    const dept = departments.find(d => d.id === id);
    if (dept && confirm(`Bạn có chắc chắn muốn xóa phòng ban "${dept.name}"?`)) {
        departments = departments.filter(d => d.id !== id);
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
        manager: formData.get('manager'),
        employees: 0,
        email: formData.get('email'),
        phone: formData.get('phone'),
        parentDept: formData.get('parentDept'),
        description: formData.get('description')
    };
    
    if (editingId) {
        const index = departments.findIndex(d => d.id === editingId);
        if (index !== -1) {
            departments[index] = { ...departments[index], ...newData };
            alert('Đã cập nhật!');
        }
    } else {
        const newId = Math.max(...departments.map(d => d.id), 0) + 1;
        departments.push({ id: newId, ...newData });
        alert('Đã thêm mới!');
    }
    
    loadData();
    closeModal();
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) {
        closeModal();
    }
}
