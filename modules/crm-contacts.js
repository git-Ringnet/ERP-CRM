let data = [
    { id: 1, type: 'call', customer: 'Công ty TNHH ABC', title: 'Tư vấn sản phẩm mới', employee: 'Nguyễn Văn A', contactDate: '2025-11-09', contactTime: '14:00', content: 'Giới thiệu sản phẩm laptop mới cho khách hàng', opportunity: 100000000, successRate: 70, status: 'completed', result: 'Khách hàng quan tâm, yêu cầu báo giá', nextAction: 'Gửi báo giá chi tiết', nextDate: '2025-11-12', note: '' },
    { id: 2, type: 'meeting', customer: 'Công ty CP XYZ', title: 'Họp bàn về dự án', employee: 'Trần Thị B', contactDate: '2025-11-10', contactTime: '10:00', content: 'Thảo luận về dự án cung cấp thiết bị văn phòng', opportunity: 500000000, successRate: 80, status: 'planned', result: '', nextAction: '', nextDate: '', note: 'Chuẩn bị tài liệu demo' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
    setupFilters();
    setDefaultDate();
});

function setDefaultDate() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('[name="contactDate"]').value = today;
}

function loadData(typeFilter = '', statusFilter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    
    if (typeFilter) filteredData = filteredData.filter(d => d.type === typeFilter);
    if (statusFilter) filteredData = filteredData.filter(d => d.status === statusFilter);
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const typeIcon = getTypeIcon(item.type);
        const statusBadge = getStatusBadge(item.status);
        
        tbody.innerHTML += `
            <tr>
                <td>${typeIcon}</td>
                <td>${item.customer}</td>
                <td>${item.title}</td>
                <td>${item.employee}</td>
                <td>${formatDate(item.contactDate)} ${item.contactTime || ''}</td>
                <td>${item.opportunity ? formatCurrency(item.opportunity) + ' (' + item.successRate + '%)' : 'N/A'}</td>
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

function getTypeIcon(type) {
    const icons = {
        'call': '<span class="badge badge-normal"><i class="fas fa-phone"></i> Cuộc gọi</span>',
        'meeting': '<span class="badge badge-vip"><i class="fas fa-users"></i> Cuộc họp</span>',
        'email': '<span class="badge badge-serial"><i class="fas fa-envelope"></i> Email</span>',
        'visit': '<span class="badge badge-lot"><i class="fas fa-map-marker-alt"></i> Thăm KH</span>'
    };
    return icons[type] || '';
}

function getStatusBadge(status) {
    const badges = {
        'planned': '<span class="badge badge-pending">Đã lên kế hoạch</span>',
        'completed': '<span class="badge badge-approved">Đã hoàn thành</span>',
        'cancelled': '<span class="badge badge-rejected">Đã hủy</span>'
    };
    return badges[status] || '';
}

function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#tableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
}

function setupFilters() {
    document.getElementById('filterType').addEventListener('change', function() {
        loadData(this.value, document.getElementById('filterStatus').value);
    });
    document.getElementById('filterStatus').addEventListener('change', function() {
        loadData(document.getElementById('filterType').value, this.value);
    });
}

function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm liên hệ mới';
    document.getElementById('dataForm').reset();
    setDefaultDate();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Thông tin liên hệ:\n\nLoại: ${getTypeText(item.type)}\nKhách hàng: ${item.customer}\nTiêu đề: ${item.title}\nNhân viên: ${item.employee}\nNgày: ${formatDate(item.contactDate)} ${item.contactTime || ''}\nNội dung: ${item.content}\nCơ hội: ${item.opportunity ? formatCurrency(item.opportunity) : 'N/A'}\nTỷ lệ thành công: ${item.successRate}%\nKết quả: ${item.result || 'Chưa có'}\nHành động tiếp theo: ${item.nextAction || 'Chưa có'}`);
    }
}

function getTypeText(type) {
    const texts = {
        'call': 'Cuộc gọi',
        'meeting': 'Cuộc họp',
        'email': 'Email',
        'visit': 'Thăm khách hàng'
    };
    return texts[type] || '';
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa liên hệ';
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
    if (item && confirm(`Bạn có chắc chắn muốn xóa liên hệ "${item.title}"?`)) {
        data = data.filter(d => d.id !== id);
        loadData();
        alert('Đã xóa thành công!');
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const newData = {
        type: formData.get('type'),
        customer: formData.get('customer'),
        title: formData.get('title'),
        employee: formData.get('employee'),
        contactDate: formData.get('contactDate'),
        contactTime: formData.get('contactTime'),
        content: formData.get('content'),
        opportunity: parseFloat(formData.get('opportunity')) || 0,
        successRate: parseInt(formData.get('successRate')) || 0,
        status: formData.get('status'),
        result: formData.get('result'),
        nextAction: formData.get('nextAction'),
        nextDate: formData.get('nextDate'),
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = data.findIndex(d => d.id === editingId);
        if (index !== -1) {
            data[index] = { ...data[index], ...newData };
            alert('Đã cập nhật liên hệ!');
        }
    } else {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        data.push({ id: newId, ...newData });
        alert('Đã thêm liên hệ mới!');
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

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN');
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) {
        closeModal();
    }
}
