let data = [
    { id: 1, code: 'NV001', employee: 'Nguyễn Văn A', department: 'IT', skillName: 'JavaScript', skillType: 'technical', level: 'expert', experience: 5, certificate: 'Có', issuer: '', issueDate: '', expiryDate: '', note: '' },
    { id: 2, code: 'NV001', employee: 'Nguyễn Văn A', department: 'IT', skillName: 'React', skillType: 'technical', level: 'advanced', experience: 3, certificate: 'Không', issuer: '', issueDate: '', expiryDate: '', note: '' },
    { id: 3, code: 'NV002', employee: 'Trần Thị B', department: 'Kinh doanh', skillName: 'Giao tiếp', skillType: 'soft', level: 'expert', experience: 7, certificate: 'Không', issuer: '', issueDate: '', expiryDate: '', note: '' },
    { id: 4, code: 'NV002', employee: 'Trần Thị B', department: 'Kinh doanh', skillName: 'PMP', skillType: 'certificate', level: 'expert', experience: 5, certificate: 'Có', issuer: 'PMI', issueDate: '2020-01-15', expiryDate: '2026-01-15', note: 'Chứng chỉ quản lý dự án' },
    { id: 5, code: 'NV003', employee: 'Lê Văn C', department: 'Kế toán', skillName: 'Excel', skillType: 'technical', level: 'advanced', experience: 4, certificate: 'Không', issuer: '', issueDate: '', expiryDate: '', note: '' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadAllSkills();
    loadTechnicalSkills();
    loadSoftSkills();
    loadCertificates();
    setupSearch();
    updateDashboard();
});

function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

function loadAllSkills() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    data.forEach(item => {
        const typeBadge = getTypeBadge(item.skillType);
        const levelBadge = getLevelBadge(item.level);
        const certificateBadge = item.certificate === 'Có' ? '<span class="badge badge-approved">Có</span>' : '<span class="badge badge-normal">Không</span>';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.employee}</td>
                <td>${item.department}</td>
                <td><strong>${item.skillName}</strong></td>
                <td>${typeBadge}</td>
                <td>${levelBadge}</td>
                <td>${certificateBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function loadTechnicalSkills() {
    const tbody = document.getElementById('technicalTableBody');
    tbody.innerHTML = '';
    
    const technicalSkills = data.filter(item => item.skillType === 'technical');
    technicalSkills.forEach(item => {
        const levelBadge = getLevelBadge(item.level);
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.employee}</strong></td>
                <td>${item.skillName}</td>
                <td>${levelBadge}</td>
                <td>${item.experience} năm</td>
                <td>${item.note || 'N/A'}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `;
    });
}

function loadSoftSkills() {
    const tbody = document.getElementById('softTableBody');
    tbody.innerHTML = '';
    
    const softSkills = data.filter(item => item.skillType === 'soft');
    softSkills.forEach(item => {
        const levelBadge = getLevelBadge(item.level);
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.employee}</strong></td>
                <td>${item.skillName}</td>
                <td>${levelBadge}</td>
                <td>${getLevelText(item.level)}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `;
    });
}

function loadCertificates() {
    const tbody = document.getElementById('certificateTableBody');
    tbody.innerHTML = '';
    
    const certificates = data.filter(item => item.skillType === 'certificate');
    certificates.forEach(item => {
        const statusBadge = isExpired(item.expiryDate) ? '<span class="badge badge-rejected">Hết hạn</span>' : '<span class="badge badge-approved">Còn hạn</span>';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.employee}</strong></td>
                <td>${item.skillName}</td>
                <td>${item.issuer || 'N/A'}</td>
                <td>${item.issueDate ? formatDate(item.issueDate) : 'N/A'}</td>
                <td>${item.expiryDate ? formatDate(item.expiryDate) : 'Vô thời hạn'}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon" onclick="printCertificate(${item.id})"><i class="fas fa-print"></i></button>
                </td>
            </tr>
        `;
    });
}

function getTypeBadge(type) {
    const badges = {
        'technical': '<span class="badge badge-pending">Chuyên môn</span>',
        'soft': '<span class="badge" style="background: #e7d4ff; color: #6f42c1;">Kỹ năng mềm</span>',
        'certificate': '<span class="badge badge-approved">Chứng chỉ</span>'
    };
    return badges[type] || '';
}

function getLevelBadge(level) {
    const badges = {
        'beginner': '<span class="badge badge-normal">Mới bắt đầu</span>',
        'intermediate': '<span class="badge badge-pending">Trung bình</span>',
        'advanced': '<span class="badge" style="background: #cfe2ff; color: #084298;">Nâng cao</span>',
        'expert': '<span class="badge badge-approved">Chuyên gia</span>'
    };
    return badges[level] || '';
}

function getLevelText(level) {
    const texts = {
        'beginner': 'Mới bắt đầu',
        'intermediate': 'Trung bình',
        'advanced': 'Nâng cao',
        'expert': 'Chuyên gia'
    };
    return texts[level] || '';
}

function isExpired(expiryDate) {
    if (!expiryDate) return false;
    return new Date(expiryDate) < new Date();
}

function updateDashboard() {
    const employees = [...new Set(data.map(item => item.employee))].length;
    const totalSkills = data.length;
    const technical = data.filter(item => item.skillType === 'technical').length;
    const soft = data.filter(item => item.skillType === 'soft').length;
    
    document.getElementById('totalEmployees').textContent = employees;
    document.getElementById('totalSkills').textContent = totalSkills;
    document.getElementById('technicalSkills').textContent = technical;
    document.getElementById('softSkills').textContent = soft;
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#tableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
}

function updateSkillOptions() {
    const skillType = document.querySelector('[name="skillType"]').value;
    const certificateFields = document.getElementById('certificateFields');
    
    if (skillType === 'certificate') {
        certificateFields.style.display = 'block';
    } else {
        certificateFields.style.display = 'none';
    }
}

function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm kỹ năng cho nhân viên';
    document.getElementById('dataForm').reset();
    document.getElementById('certificateFields').style.display = 'none';
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        let details = `Chi tiết kỹ năng:\n\nNhân viên: ${item.employee}\nPhòng ban: ${item.department}\nKỹ năng: ${item.skillName}\nLoại: ${getLevelText(item.level)}\nMức độ: ${getLevelText(item.level)}\nKinh nghiệm: ${item.experience} năm`;
        
        if (item.skillType === 'certificate') {
            details += `\n\nTổ chức cấp: ${item.issuer || 'N/A'}\nNgày cấp: ${item.issueDate ? formatDate(item.issueDate) : 'N/A'}\nNgày hết hạn: ${item.expiryDate ? formatDate(item.expiryDate) : 'Vô thời hạn'}`;
        }
        
        details += `\n\nGhi chú: ${item.note || 'Không có'}`;
        alert(details);
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa kỹ năng';
        const form = document.getElementById('dataForm');
        Object.keys(item).forEach(key => {
            if (form.elements[key]) {
                form.elements[key].value = item[key] || '';
            }
        });
        updateSkillOptions();
        document.getElementById('modal').style.display = 'block';
    }
}

function deleteData(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Bạn có chắc chắn muốn xóa kỹ năng "${item.skillName}" của ${item.employee}?`)) {
        data = data.filter(d => d.id !== id);
        loadAllSkills();
        loadTechnicalSkills();
        loadSoftSkills();
        loadCertificates();
        updateDashboard();
        alert('Đã xóa thành công!');
    }
}

function printCertificate(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`In chứng chỉ: ${item.skillName}\nNhân viên: ${item.employee}\n\nChức năng đang được phát triển...`);
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const employee = formData.get('employee');
    const employeeData = {
        'Nguyễn Văn A': { code: 'NV001', department: 'IT' },
        'Trần Thị B': { code: 'NV002', department: 'Kinh doanh' },
        'Lê Văn C': { code: 'NV003', department: 'Kế toán' }
    };
    
    const empInfo = employeeData[employee] || { code: 'NV999', department: 'Khác' };
    
    const newData = {
        code: empInfo.code,
        employee: employee,
        department: empInfo.department,
        skillName: formData.get('skillName'),
        skillType: formData.get('skillType'),
        level: formData.get('level'),
        experience: parseFloat(formData.get('experience')) || 0,
        certificate: formData.get('skillType') === 'certificate' ? 'Có' : 'Không',
        issuer: formData.get('issuer'),
        issueDate: formData.get('issueDate'),
        expiryDate: formData.get('expiryDate'),
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
    
    loadAllSkills();
    loadTechnicalSkills();
    loadSoftSkills();
    loadCertificates();
    updateDashboard();
    closeModal();
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
