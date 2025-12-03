let data = [
    { id: 1, code: 'KHO001', name: 'Kho chính', type: 'physical', address: 'Số 123, Đường ABC, Hà Nội', area: 500, capacity: 100, manager: 'Nguyễn Văn A', phone: '0901234567', status: 'active', productType: 'Điện tử', hasTemperatureControl: true, hasSecuritySystem: true, note: '' },
    { id: 2, code: 'KHO002', name: 'Kho phụ', type: 'physical', address: 'Số 456, Đường XYZ, TP.HCM', area: 300, capacity: 50, manager: 'Trần Thị B', phone: '0912345678', status: 'active', productType: 'Văn phòng phẩm', hasTemperatureControl: false, hasSecuritySystem: true, note: '' },
    { id: 3, code: 'KHO003', name: 'Kho ảo - Hàng đặt trước', type: 'virtual', address: 'N/A', area: 0, capacity: 0, manager: 'Lê Văn C', phone: '0923456789', status: 'active', productType: 'Tất cả', hasTemperatureControl: false, hasSecuritySystem: false, note: 'Kho ảo để quản lý hàng đặt trước' }
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
    if (filter) filteredData = data.filter(d => d.type === filter);
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const typeBadge = item.type === 'physical' ? '<span class="badge badge-normal">Kho thực</span>' : '<span class="badge badge-vip">Kho ảo</span>';
        const statusBadge = getStatusBadge(item.status);
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${typeBadge}</td>
                <td>${item.address}</td>
                <td>${item.area > 0 ? item.area + ' m²' : 'N/A'}</td>
                <td>${item.manager}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon" onclick="viewInventory(${item.id})" title="Xem tồn kho"><i class="fas fa-boxes"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge badge-approved">Đang hoạt động</span>',
        'maintenance': '<span class="badge badge-pending">Bảo trì</span>',
        'inactive': '<span class="badge badge-rejected">Ngừng hoạt động</span>'
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
    document.getElementById('filterType').addEventListener('change', function() {
        loadData(this.value);
    });
}

function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm kho mới';
    document.getElementById('dataForm').reset();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        const features = [];
        if (item.hasTemperatureControl) features.push('Kiểm soát nhiệt độ');
        if (item.hasSecuritySystem) features.push('Hệ thống an ninh');
        
        alert(`Thông tin kho:\n\nMã: ${item.code}\nTên: ${item.name}\nLoại: ${item.type === 'physical' ? 'Kho thực' : 'Kho ảo'}\nĐịa chỉ: ${item.address}\nDiện tích: ${item.area} m²\nSức chứa: ${item.capacity} tấn\nNgười quản lý: ${item.manager}\nĐiện thoại: ${item.phone}\nTính năng: ${features.join(', ') || 'Không có'}\nTrạng thái: ${getStatusText(item.status)}`);
    }
}

function getStatusText(status) {
    const texts = {
        'active': 'Đang hoạt động',
        'maintenance': 'Bảo trì',
        'inactive': 'Ngừng hoạt động'
    };
    return texts[status] || '';
}

function viewInventory(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Xem tồn kho của: ${item.name}\n\nChức năng đang được phát triển...`);
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa kho';
        const form = document.getElementById('dataForm');
        
        form.elements['id'].value = item.id;
        form.elements['code'].value = item.code;
        form.elements['name'].value = item.name;
        form.elements['type'].value = item.type;
        form.elements['address'].value = item.address;
        form.elements['area'].value = item.area;
        form.elements['capacity'].value = item.capacity;
        form.elements['manager'].value = item.manager;
        form.elements['phone'].value = item.phone || '';
        form.elements['status'].value = item.status;
        form.elements['productType'].value = item.productType || '';
        form.elements['hasTemperatureControl'].checked = item.hasTemperatureControl || false;
        form.elements['hasSecuritySystem'].checked = item.hasSecuritySystem || false;
        form.elements['note'].value = item.note || '';
        
        document.getElementById('modal').style.display = 'block';
    }
}

function deleteData(id) {
    const item = data.find(d => d.id === id);
    if (item && confirm(`Bạn có chắc chắn muốn xóa kho "${item.name}"?`)) {
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
        address: formData.get('address'),
        area: parseFloat(formData.get('area')) || 0,
        capacity: parseFloat(formData.get('capacity')) || 0,
        manager: formData.get('manager'),
        phone: formData.get('phone'),
        status: formData.get('status'),
        productType: formData.get('productType'),
        hasTemperatureControl: formData.get('hasTemperatureControl') === 'on',
        hasSecuritySystem: formData.get('hasSecuritySystem') === 'on',
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = data.findIndex(d => d.id === editingId);
        if (index !== -1) {
            data[index] = { ...data[index], ...newData };
            alert('Đã cập nhật kho!');
        }
    } else {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        data.push({ id: newId, ...newData });
        alert('Đã thêm kho mới!');
    }
    
    loadData();
    closeModal();
}

function exportData() {
    alert('Chức năng xuất Excel đang được phát triển');
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) {
        closeModal();
    }
}
