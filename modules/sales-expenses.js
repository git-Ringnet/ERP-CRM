let data = [
    { id: 1, code: 'CP001', type: 'shipping', orderId: 'DH001', description: 'Chi phí vận chuyển đơn hàng DH001', amount: 500000, date: '2025-11-09', assignedTo: 'Nguyễn Văn A', note: '' },
    { id: 2, code: 'CP002', type: 'marketing', orderId: '', description: 'Chi phí quảng cáo Facebook Ads', amount: 5000000, date: '2025-11-08', assignedTo: 'Trần Thị B', note: '' },
    { id: 3, code: 'CP003', type: 'commission', orderId: 'DH002', description: 'Hoa hồng bán hàng đơn DH002', amount: 2000000, date: '2025-11-07', assignedTo: 'Nguyễn Văn A', note: '' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const currentMonth = today.toISOString().slice(0, 7);
    document.getElementById('filterMonth').value = currentMonth;
    loadData();
    setupSearch();
    updateSummary();
});

function loadData() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    data.forEach(item => {
        const typeBadge = getTypeBadge(item.type);
        const orderInfo = item.orderId || '<span style="color: #95a5a6;">N/A</span>';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${typeBadge}</td>
                <td>${orderInfo}</td>
                <td>${item.description}</td>
                <td class="text-danger"><strong>${formatCurrency(item.amount)}</strong></td>
                <td>${formatDate(item.date)}</td>
                <td>${item.assignedTo || 'N/A'}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
    
    updateSummary();
}

function getTypeBadge(type) {
    const badges = {
        'shipping': '<span class="badge badge-pending">Vận chuyển</span>',
        'marketing': '<span class="badge" style="background: #e7d4ff; color: #6f42c1;">Marketing</span>',
        'commission': '<span class="badge badge-approved">Hoa hồng</span>',
        'other': '<span class="badge badge-normal">Khác</span>'
    };
    return badges[type] || '';
}

function getTypeText(type) {
    const texts = {
        'shipping': 'Vận chuyển',
        'marketing': 'Marketing',
        'commission': 'Hoa hồng',
        'other': 'Khác'
    };
    return texts[type] || '';
}

function updateSummary() {
    const total = data.reduce((sum, item) => sum + item.amount, 0);
    const shipping = data.filter(item => item.type === 'shipping').reduce((sum, item) => sum + item.amount, 0);
    const marketing = data.filter(item => item.type === 'marketing').reduce((sum, item) => sum + item.amount, 0);
    const commission = data.filter(item => item.type === 'commission').reduce((sum, item) => sum + item.amount, 0);
    
    document.getElementById('totalExpense').textContent = formatCurrency(total);
    document.getElementById('shippingExpense').textContent = formatCurrency(shipping);
    document.getElementById('marketingExpense').textContent = formatCurrency(marketing);
    document.getElementById('commissionExpense').textContent = formatCurrency(commission);
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
    const filterType = document.getElementById('filterType').value;
    document.querySelectorAll('#tableBody tr').forEach(row => {
        if (!filterType) {
            row.style.display = '';
        } else {
            const type = row.cells[1].textContent.toLowerCase();
            row.style.display = type.includes(getTypeText(filterType).toLowerCase()) ? '' : 'none';
        }
    });
}

function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Thêm chi phí bán hàng';
    document.getElementById('dataForm').reset();
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('[name="date"]').value = today;
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Chi tiết chi phí:\n\nMã: ${item.code}\nLoại: ${getTypeText(item.type)}\nĐơn hàng: ${item.orderId || 'N/A'}\nMô tả: ${item.description}\nSố tiền: ${formatCurrency(item.amount)}\nNgày: ${formatDate(item.date)}\nNgười phụ trách: ${item.assignedTo || 'N/A'}\nGhi chú: ${item.note || 'Không có'}`);
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa chi phí';
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
    if (item && confirm(`Bạn có chắc chắn muốn xóa chi phí "${item.description}"?`)) {
        data = data.filter(d => d.id !== id);
        loadData();
        alert('Đã xóa thành công!');
    }
}

function saveData(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const newData = {
        code: 'CP' + String(data.length + 1).padStart(3, '0'),
        type: formData.get('type'),
        orderId: formData.get('orderId'),
        description: formData.get('description'),
        amount: parseFloat(formData.get('amount')),
        date: formData.get('date'),
        assignedTo: formData.get('assignedTo'),
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
