let assets = [
    { id: 1, code: 'TS001', name: 'Máy tính Dell Latitude 5420', category: 'equipment', purchaseDate: '2023-01-15', value: 20000000, depreciationYears: 5, status: 'active', location: 'Phòng IT', manager: 'Nguyễn Văn A', note: '' },
    { id: 2, code: 'TS002', name: 'Xe ô tô Toyota Vios', category: 'vehicle', purchaseDate: '2022-06-01', value: 500000000, depreciationYears: 10, status: 'active', location: 'Bãi xe', manager: 'Trần Thị B', note: '' },
    { id: 3, code: 'TS003', name: 'Bàn làm việc gỗ', category: 'furniture', purchaseDate: '2024-01-10', value: 5000000, depreciationYears: 7, status: 'active', location: 'Văn phòng', manager: '', note: '' }
];

let serialNumbers = [
    { id: 1, serial: 'SN-2025-123456', assetName: 'Máy tính Dell Latitude 5420', createdDate: '2025-01-15', status: 'active' },
    { id: 2, serial: 'SN-2025-234567', assetName: 'iPhone 13 Pro', createdDate: '2025-02-01', status: 'active' }
];

let lotNumbers = [
    { id: 1, lot: 'LOT-2025-11-001', assetName: 'Bàn ghế văn phòng', quantity: 50, createdDate: '2025-11-01', expiryDate: '2030-11-01' },
    { id: 2, lot: 'LOT-2025-11-002', assetName: 'Giấy A4', quantity: 100, createdDate: '2025-11-05', expiryDate: '2027-11-05' }
];

let internalSerials = [
    { id: 1, serial: 'TS001-2025-11-00001', assetName: 'Máy tính Dell', location: 'Phòng IT', manager: 'Nguyễn Văn A', createdDate: '2025-11-01' },
    { id: 2, serial: 'TS002-2025-11-00002', assetName: 'Máy in HP', location: 'Phòng Hành chính', manager: 'Trần Thị B', createdDate: '2025-11-05' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadAllAssets();
    loadSerialNumbers();
    loadLotNumbers();
    loadInternalSerials();
    setupSearch();
});

function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

function loadAllAssets() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    assets.forEach(asset => {
        const categoryBadge = getCategoryBadge(asset.category);
        const statusBadge = getStatusBadge(asset.status);
        const depreciation = calculateDepreciation(asset);
        const remainingValue = asset.value - depreciation;
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${asset.code}</strong></td>
                <td>${asset.name}</td>
                <td>${categoryBadge}</td>
                <td>${formatDate(asset.purchaseDate)}</td>
                <td>${formatCurrency(asset.value)}</td>
                <td class="text-warning">${formatCurrency(depreciation)}</td>
                <td class="text-success"><strong>${formatCurrency(remainingValue)}</strong></td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewAsset(${asset.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editAsset(${asset.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteAsset(${asset.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

function loadSerialNumbers() {
    const tbody = document.getElementById('serialTableBody');
    tbody.innerHTML = '';
    
    serialNumbers.forEach(item => {
        const statusBadge = item.status === 'active' ? '<span class="badge badge-approved">Đang sử dụng</span>' : '<span class="badge badge-normal">Không sử dụng</span>';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.serial}</strong></td>
                <td>${item.assetName}</td>
                <td>${formatDate(item.createdDate)}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-view"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon"><i class="fas fa-print"></i></button>
                </td>
            </tr>
        `;
    });
}

function loadLotNumbers() {
    const tbody = document.getElementById('lotTableBody');
    tbody.innerHTML = '';
    
    lotNumbers.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.lot}</strong></td>
                <td>${item.assetName}</td>
                <td><span class="badge badge-normal">${item.quantity}</span></td>
                <td>${formatDate(item.createdDate)}</td>
                <td>${formatDate(item.expiryDate)}</td>
                <td>
                    <button class="btn-icon btn-view"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon"><i class="fas fa-print"></i></button>
                </td>
            </tr>
        `;
    });
}

function loadInternalSerials() {
    const tbody = document.getElementById('internalTableBody');
    tbody.innerHTML = '';
    
    internalSerials.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.serial}</strong></td>
                <td>${item.assetName}</td>
                <td>${item.location}</td>
                <td>${item.manager}</td>
                <td>${formatDate(item.createdDate)}</td>
                <td>
                    <button class="btn-icon btn-view"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon"><i class="fas fa-qrcode"></i></button>
                </td>
            </tr>
        `;
    });
}

function getCategoryBadge(category) {
    const badges = {
        'equipment': '<span class="badge badge-pending">Thiết bị</span>',
        'vehicle': '<span class="badge" style="background: #e7d4ff; color: #6f42c1;">Phương tiện</span>',
        'furniture': '<span class="badge badge-normal">Nội thất</span>',
        'other': '<span class="badge badge-normal">Khác</span>'
    };
    return badges[category] || '';
}

function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge badge-approved">Đang sử dụng</span>',
        'maintenance': '<span class="badge badge-pending">Bảo trì</span>',
        'inactive': '<span class="badge badge-normal">Không sử dụng</span>',
        'disposed': '<span class="badge badge-rejected">Đã thanh lý</span>'
    };
    return badges[status] || '';
}

function calculateDepreciation(asset) {
    const purchaseDate = new Date(asset.purchaseDate);
    const today = new Date();
    const yearsUsed = (today - purchaseDate) / (1000 * 60 * 60 * 24 * 365);
    const annualDepreciation = asset.value / asset.depreciationYears;
    const totalDepreciation = annualDepreciation * yearsUsed;
    return Math.min(totalDepreciation, asset.value);
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
    document.getElementById('modalTitle').textContent = 'Thêm tài sản';
    document.getElementById('dataForm').reset();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewAsset(id) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        const depreciation = calculateDepreciation(asset);
        const remainingValue = asset.value - depreciation;
        alert(`Chi tiết tài sản:\n\nMã: ${asset.code}\nTên: ${asset.name}\nLoại: ${asset.category}\nNgày mua: ${formatDate(asset.purchaseDate)}\nGiá trị: ${formatCurrency(asset.value)}\nKhấu hao: ${formatCurrency(depreciation)}\nGiá trị còn lại: ${formatCurrency(remainingValue)}\nVị trí: ${asset.location || 'N/A'}\nNgười quản lý: ${asset.manager || 'N/A'}`);
    }
}

function editAsset(id) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa tài sản';
        const form = document.getElementById('dataForm');
        Object.keys(asset).forEach(key => {
            if (form.elements[key]) {
                form.elements[key].value = asset[key] || '';
            }
        });
        document.getElementById('modal').style.display = 'block';
    }
}

function deleteAsset(id) {
    const asset = assets.find(a => a.id === id);
    if (asset && confirm(`Bạn có chắc chắn muốn xóa tài sản "${asset.name}"?`)) {
        assets = assets.filter(a => a.id !== id);
        loadAllAssets();
        alert('Đã xóa thành công!');
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const newData = {
        code: formData.get('code'),
        name: formData.get('name'),
        category: formData.get('category'),
        purchaseDate: formData.get('purchaseDate'),
        value: parseFloat(formData.get('value')),
        depreciationYears: parseInt(formData.get('depreciationYears')) || 5,
        status: formData.get('status'),
        location: formData.get('location'),
        manager: formData.get('manager'),
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = assets.findIndex(a => a.id === editingId);
        if (index !== -1) {
            assets[index] = { ...assets[index], ...newData };
            alert('Đã cập nhật!');
        }
    } else {
        const newId = Math.max(...assets.map(a => a.id), 0) + 1;
        assets.push({ id: newId, ...newData });
        alert('Đã thêm mới!');
    }
    
    loadAllAssets();
    closeModal();
}

function generateSerialNumber() {
    const year = new Date().getFullYear();
    const random = String(Math.floor(Math.random() * 999999)).padStart(6, '0');
    const serial = `SN-${year}-${random}`;
    
    if (confirm(`Tạo serial number mới: ${serial}?`)) {
        const newId = Math.max(...serialNumbers.map(s => s.id), 0) + 1;
        serialNumbers.push({
            id: newId,
            serial: serial,
            assetName: 'Tài sản mới',
            createdDate: new Date().toISOString().split('T')[0],
            status: 'active'
        });
        loadSerialNumbers();
        alert(`Đã tạo serial: ${serial}`);
    }
}

function generateLotNumber() {
    const year = new Date().getFullYear();
    const month = String(new Date().getMonth() + 1).padStart(2, '0');
    const random = String(Math.floor(Math.random() * 999)).padStart(3, '0');
    const lot = `LOT-${year}-${month}-${random}`;
    
    if (confirm(`Tạo số lô mới: ${lot}?`)) {
        const newId = Math.max(...lotNumbers.map(l => l.id), 0) + 1;
        const today = new Date();
        const expiryDate = new Date(today.setFullYear(today.getFullYear() + 2));
        
        lotNumbers.push({
            id: newId,
            lot: lot,
            assetName: 'Tài sản mới',
            quantity: 0,
            createdDate: new Date().toISOString().split('T')[0],
            expiryDate: expiryDate.toISOString().split('T')[0]
        });
        loadLotNumbers();
        alert(`Đã tạo số lô: ${lot}`);
    }
}

function generateInternalSerial() {
    const assetCode = prompt('Nhập mã tài sản (VD: TS001):');
    if (assetCode) {
        const year = new Date().getFullYear();
        const month = String(new Date().getMonth() + 1).padStart(2, '0');
        const random = String(Math.floor(Math.random() + 1) + internalSerials.length).padStart(5, '0');
        const serial = `${assetCode}-${year}-${month}-${random}`;
        
        const newId = Math.max(...internalSerials.map(s => s.id), 0) + 1;
        internalSerials.push({
            id: newId,
            serial: serial,
            assetName: 'Tài sản mới',
            location: 'Chưa xác định',
            manager: '',
            createdDate: new Date().toISOString().split('T')[0]
        });
        loadInternalSerials();
        alert(`Đã tạo serial nội bộ: ${serial}`);
    }
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
