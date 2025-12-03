let data = [
    { id: 1, customer: 'Công ty TNHH ABC', debtLimit: 200000000, currentDebt: 50000000, debtDays: 30, warningPercent: 80, blockWhenExceed: false, note: '' },
    { id: 2, customer: 'Công ty CP XYZ', debtLimit: 100000000, currentDebt: 85000000, debtDays: 15, warningPercent: 80, blockWhenExceed: true, note: 'Khách hàng thường xuyên trễ hạn' },
    { id: 3, customer: 'Công ty TNHH DEF', debtLimit: 150000000, currentDebt: 120000000, debtDays: 30, warningPercent: 80, blockWhenExceed: false, note: '' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
    setupFilter();
});

function loadData(statusFilter = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = data;
    
    if (statusFilter) {
        filteredData = data.filter(d => {
            const percent = (d.currentDebt / d.debtLimit) * 100;
            if (statusFilter === 'safe') return percent < 50;
            if (statusFilter === 'warning') return percent >= 50 && percent < 80;
            if (statusFilter === 'danger') return percent >= 80;
            return true;
        });
    }
    
    tbody.innerHTML = '';
    filteredData.forEach(item => {
        const remaining = item.debtLimit - item.currentDebt;
        const percent = ((item.currentDebt / item.debtLimit) * 100).toFixed(1);
        const statusInfo = getStatusInfo(percent);
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.customer}</strong></td>
                <td>${formatCurrency(item.debtLimit)}</td>
                <td class="${statusInfo.class}">${formatCurrency(item.currentDebt)}</td>
                <td>${formatCurrency(remaining)}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="progress-bar" style="flex: 1;">
                            <div class="progress-fill" style="width: ${percent}%; background: ${statusInfo.color};">
                                ${percent}%
                            </div>
                        </div>
                    </div>
                </td>
                <td>${statusInfo.badge}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `;
    });
}

function getStatusInfo(percent) {
    if (percent < 50) {
        return {
            class: 'text-success',
            color: '#27ae60',
            badge: '<span class="badge badge-approved">An toàn</span>'
        };
    } else if (percent < 80) {
        return {
            class: 'text-warning',
            color: '#f39c12',
            badge: '<span class="badge badge-pending">Gần hạn mức</span>'
        };
    } else {
        return {
            class: 'text-danger',
            color: '#e74c3c',
            badge: '<span class="badge badge-rejected">Vượt hạn mức</span>'
        };
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
    document.getElementById('filterStatus').addEventListener('change', function() {
        loadData(this.value);
    });
}

function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thiết lập hạn mức công nợ';
    document.getElementById('dataForm').reset();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        const percent = ((item.currentDebt / item.debtLimit) * 100).toFixed(1);
        const remaining = item.debtLimit - item.currentDebt;
        alert(`Thông tin hạn mức công nợ:\n\nKhách hàng: ${item.customer}\nHạn mức: ${formatCurrency(item.debtLimit)}\nCông nợ hiện tại: ${formatCurrency(item.currentDebt)}\nCòn lại: ${formatCurrency(remaining)}\nTỷ lệ sử dụng: ${percent}%\nSố ngày công nợ: ${item.debtDays} ngày\nCảnh báo khi đạt: ${item.warningPercent}%\nChặn khi vượt: ${item.blockWhenExceed ? 'Có' : 'Không'}`);
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa hạn mức công nợ';
        const form = document.getElementById('dataForm');
        
        form.elements['id'].value = item.id;
        form.elements['customer'].value = item.customer;
        form.elements['debtLimit'].value = item.debtLimit;
        form.elements['debtDays'].value = item.debtDays;
        form.elements['warningPercent'].value = item.warningPercent;
        form.elements['blockWhenExceed'].checked = item.blockWhenExceed;
        form.elements['note'].value = item.note || '';
        
        document.getElementById('modal').style.display = 'block';
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const newData = {
        customer: formData.get('customer'),
        debtLimit: parseFloat(formData.get('debtLimit')),
        debtDays: parseInt(formData.get('debtDays')),
        warningPercent: parseInt(formData.get('warningPercent')),
        blockWhenExceed: formData.get('blockWhenExceed') === 'on',
        note: formData.get('note')
    };
    
    if (editingId) {
        const index = data.findIndex(d => d.id === editingId);
        if (index !== -1) {
            data[index] = { ...data[index], ...newData };
            alert('Đã cập nhật hạn mức công nợ!');
        }
    } else {
        const newId = Math.max(...data.map(d => d.id), 0) + 1;
        data.push({ id: newId, currentDebt: 0, ...newData });
        alert('Đã thiết lập hạn mức công nợ mới!');
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
