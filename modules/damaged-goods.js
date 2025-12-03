let data = [
    { id: 1, code: 'HH001', type: 'damaged', product: 'Laptop Dell XPS 15', quantity: 2, originalValue: 50000000, recoveryValue: 5000000, reason: 'Hư hỏng bo mạch chủ, không sửa được', status: 'completed', discoveryDate: '2025-11-01', discoveredBy: 'Nguyễn Văn A', solution: 'Bán phế liệu', note: '' },
    { id: 2, code: 'TL001', type: 'liquidation', product: 'Màn hình LG 27 inch', quantity: 10, originalValue: 55000000, recoveryValue: 30000000, reason: 'Sản phẩm cũ, thanh lý để nhập hàng mới', status: 'approved', discoveryDate: '2025-11-05', discoveredBy: 'Trần Thị B', solution: 'Bán thanh lý cho nhân viên', note: '' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
    setupFilters();
});

function loadData(typeFilter = '', statusFilter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    
    if (typeFilter) filteredData = filteredData.filter(d => d.type === typeFilter);
    if (statusFilter) filteredData = filteredData.filter(d => d.status === statusFilter);
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const typeBadge = item.type === 'damaged' ? '<span class="badge badge-rejected">Hàng hủy</span>' : '<span class="badge badge-pending">Thanh lý</span>';
        const statusBadge = getStatusBadge(item.status);
        const damage = item.originalValue - item.recoveryValue;
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${typeBadge}</td>
                <td>${item.product}</td>
                <td>${item.quantity}</td>
                <td class="text-danger">${formatCurrency(damage)}</td>
                <td>${item.reason}</td>
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
    const badges = {
        'pending': '<span class="badge badge-pending">Chờ xử lý</span>',
        'approved': '<span class="badge badge-approved">Đã duyệt</span>',
        'completed': '<span class="badge badge-approved">Đã hoàn thành</span>'
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
    document.getElementById('modalTitle').textContent = 'Đăng ký hàng hủy/thanh lý';
    document.getElementById('dataForm').reset();
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('[name="discoveryDate"]').value = today;
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        const damage = item.originalValue - item.recoveryValue;
        alert(`Thông tin:\n\nMã: ${item.code}\nLoại: ${item.type === 'damaged' ? 'Hàng hủy' : 'Thanh lý'}\nSản phẩm: ${item.product}\nSố lượng: ${item.quantity}\nGiá trị gốc: ${formatCurrency(item.originalValue)}\nGiá trị thu hồi: ${formatCurrency(item.recoveryValue)}\nThiệt hại: ${formatCurrency(damage)}\nLý do: ${item.reason}\nPhương án: ${item.solution || 'Chưa có'}`);
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa';
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
    if (item && confirm(`Bạn có chắc chắn muốn xóa "${item.code}"?`)) {
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
        type: formData.get('type'),
        product: formData.get('product'),
        quantity: parseInt(formData.get('quantity')),
        originalValue: parseFloat(formData.get('originalValue')) || 0,
        recoveryValue: parseFloat(formData.get('recoveryValue')) || 0,
        reason: formData.get('reason'),
        status: 'pending',
        discoveryDate: formData.get('discoveryDate'),
        discoveredBy: formData.get('discoveredBy'),
        solution: formData.get('solution'),
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

window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) {
        closeModal();
    }
}
