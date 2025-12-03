let data = [
    { id: 1, code: 'CC001', name: 'Laptop Dell Latitude 5420', type: 'Laptop', assignedTo: 'Nguyễn Văn A', assignDate: '2025-01-15', value: 20000000, purchaseDate: '2025-01-10', status: 'assigned', note: '' },
    { id: 2, code: 'CC002', name: 'iPhone 13 Pro', type: 'Điện thoại', assignedTo: 'Trần Thị B', assignDate: '2025-02-01', value: 25000000, purchaseDate: '2025-01-25', status: 'assigned', note: '' },
    { id: 3, code: 'CC003', name: 'Máy in HP LaserJet', type: 'Máy in', assignedTo: '', assignDate: null, value: 5000000, purchaseDate: '2025-03-01', status: 'available', note: '' },
    { id: 4, code: 'CC004', name: 'Laptop Dell XPS 15', type: 'Laptop', assignedTo: 'Lê Văn C', assignDate: '2024-12-01', value: 30000000, purchaseDate: '2024-11-20', status: 'maintenance', note: 'Đang sửa màn hình' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
    setupFilter();
    updateDashboard();
});

function updateDashboard() {
    const totalTools = data.length;
    const availableTools = data.filter(item => item.status === 'available').length;
    const assignedTools = data.filter(item => item.status === 'assigned').length;
    const totalValue = data.reduce((sum, item) => sum + item.value, 0);
    
    document.getElementById('totalTools').textContent = totalTools;
    document.getElementById('availableTools').textContent = availableTools;
    document.getElementById('assignedTools').textContent = assignedTools;
    document.getElementById('totalValue').textContent = formatCurrency(totalValue);
}

function loadData(statusFilter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    if (statusFilter) filteredData = data.filter(d => d.status === statusFilter);
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const statusBadge = getStatusBadge(item.status);
        const assignInfo = item.assignedTo || '<span style="color: #95a5a6;">Chưa cấp phát</span>';
        const assignDateInfo = item.assignDate ? formatDate(item.assignDate) : 'N/A';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${item.type}</td>
                <td>${assignInfo}</td>
                <td>${assignDateInfo}</td>
                <td>${formatCurrency(item.value)}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon" onclick="assignTool(${item.id})" title="Cấp phát"><i class="fas fa-hand-holding"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function getStatusBadge(status) {
    const badges = {
        'available': '<span class="badge badge-approved">Có sẵn</span>',
        'assigned': '<span class="badge badge-pending">Đã cấp phát</span>',
        'maintenance': '<span class="badge badge-pending">Đang bảo trì</span>',
        'damaged': '<span class="badge badge-rejected">Hỏng</span>'
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

function setupFilter() {
    document.getElementById('filterStatus').addEventListener('change', function() {
        loadData(this.value);
    });
}

function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm công cụ dụng cụ';
    document.getElementById('dataForm').reset();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Thông tin công cụ:\n\nMã: ${item.code}\nTên: ${item.name}\nLoại: ${item.type}\nGiá trị: ${formatCurrency(item.value)}\nNgày mua: ${item.purchaseDate ? formatDate(item.purchaseDate) : 'N/A'}\n\nNhân viên: ${item.assignedTo || 'Chưa cấp phát'}\nNgày cấp: ${item.assignDate ? formatDate(item.assignDate) : 'N/A'}\nTrạng thái: ${getStatusText(item.status)}`);
    }
}

function getStatusText(status) {
    const texts = {
        'available': 'Có sẵn',
        'assigned': 'Đã cấp phát',
        'maintenance': 'Đang bảo trì',
        'damaged': 'Hỏng'
    };
    return texts[status] || '';
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa công cụ';
        const form = document.getElementById('dataForm');
        Object.keys(item).forEach(key => {
            if (form.elements[key]) {
                form.elements[key].value = item[key] || '';
            }
        });
        document.getElementById('modal').style.display = 'block';
    }
}

function assignTool(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        if (item.status === 'assigned') {
            if (confirm(`Thu hồi công cụ "${item.name}" từ ${item.assignedTo}?`)) {
                const index = data.findIndex(d => d.id === id);
                data[index].assignedTo = '';
                data[index].assignDate = null;
                data[index].status = 'available';
                loadData();
                alert('Đã thu hồi công cụ!');
            }
        } else {
            alert('Cấp phát công cụ\n\nChức năng đang được phát triển...');
        }
    }
}

function deleteData(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Bạn có chắc chắn muốn xóa "${item.name}"?`)) {
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
        type: formData.get('type'),
        assignedTo: formData.get('assignedTo'),
        assignDate: formData.get('assignDate'),
        value: parseFloat(formData.get('value')) || 0,
        purchaseDate: formData.get('purchaseDate'),
        status: formData.get('status'),
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
    updateDashboard();
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
