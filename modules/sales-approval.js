let orders = {
    level1: [
        { id: 1, code: 'DH001', customer: 'Công ty TNHH ABC', total: 100000000, currentDebt: 50000000, debtLimit: 200000000, creator: 'Nguyễn Văn A', createDate: '2025-11-09' }
    ],
    level2: [],
    approved: [
        { id: 3, code: 'DH003', customer: 'Công ty TNHH DEF', total: 75000000, margin: 18, approver1: 'Trần Thị B', approver2: 'Lê Văn C', approveDate: '2025-11-08' }
    ],
    rejected: []
};

let currentOrderId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadAllData();
});

function loadAllData() {
    loadLevel1();
    loadLevel2();
    loadApproved();
    loadRejected();
}

function loadLevel1() {
    const tbody = document.getElementById('level1Body');
    tbody.innerHTML = '';
    
    if (orders.level1.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Không có đơn hàng chờ duyệt cấp 1</td></tr>';
        return;
    }
    
    orders.level1.forEach(order => {
        const debtPercent = (order.currentDebt / order.debtLimit * 100).toFixed(1);
        const debtClass = debtPercent > 80 ? 'text-danger' : debtPercent > 50 ? 'text-warning' : 'text-success';
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${order.code}</strong></td>
                <td>${order.customer}</td>
                <td>${formatCurrency(order.total)}</td>
                <td class="${debtClass}">${formatCurrency(order.currentDebt)}</td>
                <td>${formatCurrency(order.debtLimit)}</td>
                <td>${order.creator}</td>
                <td>${formatDate(order.createDate)}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewOrder(${order.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-primary" style="padding: 5px 12px; font-size: 13px;" onclick="approveLevel1(${order.id})">
                        <i class="fas fa-check"></i> Duyệt
                    </button>
                    <button class="btn-secondary" style="padding: 5px 12px; font-size: 13px; background: #e74c3c;" onclick="openRejectModal(${order.id})">
                        <i class="fas fa-times"></i> Từ chối
                    </button>
                </td>
            </tr>
        `;
    });
}

function loadLevel2() {
    const tbody = document.getElementById('level2Body');
    tbody.innerHTML = '';
    
    if (orders.level2.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Không có đơn hàng chờ duyệt cấp 2</td></tr>';
        return;
    }
    
    orders.level2.forEach(order => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${order.code}</strong></td>
                <td>${order.customer}</td>
                <td>${formatCurrency(order.total)}</td>
                <td><span class="text-success">${order.margin}%</span></td>
                <td>${order.approver1}</td>
                <td>${formatDate(order.approveDate1)}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewOrder(${order.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-primary" style="padding: 5px 12px; font-size: 13px;" onclick="approveLevel2(${order.id})">
                        <i class="fas fa-check"></i> Duyệt
                    </button>
                    <button class="btn-secondary" style="padding: 5px 12px; font-size: 13px; background: #e74c3c;" onclick="openRejectModal(${order.id})">
                        <i class="fas fa-times"></i> Từ chối
                    </button>
                </td>
            </tr>
        `;
    });
}

function loadApproved() {
    const tbody = document.getElementById('approvedBody');
    tbody.innerHTML = '';
    
    if (orders.approved.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có đơn hàng được duyệt</td></tr>';
        return;
    }
    
    orders.approved.forEach(order => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${order.code}</strong></td>
                <td>${order.customer}</td>
                <td>${formatCurrency(order.total)}</td>
                <td>${order.approver1}</td>
                <td>${order.approver2}</td>
                <td>${formatDate(order.approveDate)}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewOrder(${order.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `;
    });
}

function loadRejected() {
    const tbody = document.getElementById('rejectedBody');
    tbody.innerHTML = '';
    
    if (orders.rejected.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Không có đơn hàng bị từ chối</td></tr>';
        return;
    }
    
    orders.rejected.forEach(order => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${order.code}</strong></td>
                <td>${order.customer}</td>
                <td>${formatCurrency(order.total)}</td>
                <td>${order.rejector}</td>
                <td>${formatDate(order.rejectDate)}</td>
                <td>${order.rejectReason}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewOrder(${order.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `;
    });
}

function switchTab(tabName) {
    // Remove active class
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

function viewOrder(id) {
    alert(`Xem chi tiết đơn hàng ID: ${id}`);
}

function approveLevel1(id) {
    if (confirm('Bạn có chắc chắn muốn duyệt đơn hàng này (Cấp 1)?')) {
        const order = orders.level1.find(o => o.id === id);
        if (order) {
            // Move to level 2
            orders.level2.push({
                ...order,
                margin: 15,
                approver1: 'Trưởng phòng Kinh doanh',
                approveDate1: new Date().toISOString().split('T')[0]
            });
            orders.level1 = orders.level1.filter(o => o.id !== id);
            
            loadAllData();
            alert('Đã duyệt cấp 1! Đơn hàng chuyển sang chờ duyệt cấp 2.');
        }
    }
}

function approveLevel2(id) {
    if (confirm('Bạn có chắc chắn muốn duyệt đơn hàng này (Cấp 2)?')) {
        const order = orders.level2.find(o => o.id === id);
        if (order) {
            // Move to approved
            orders.approved.push({
                ...order,
                approver2: 'Giám đốc',
                approveDate: new Date().toISOString().split('T')[0]
            });
            orders.level2 = orders.level2.filter(o => o.id !== id);
            
            loadAllData();
            alert('Đã duyệt cấp 2! Đơn hàng được phê duyệt hoàn toàn.');
        }
    }
}

function openRejectModal(id) {
    currentOrderId = id;
    document.getElementById('rejectForm').reset();
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    currentOrderId = null;
}

function submitReject(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const reason = formData.get('reason');
    
    // Find order in level1 or level2
    let order = orders.level1.find(o => o.id === currentOrderId);
    let fromLevel = 'level1';
    
    if (!order) {
        order = orders.level2.find(o => o.id === currentOrderId);
        fromLevel = 'level2';
    }
    
    if (order) {
        // Move to rejected
        orders.rejected.push({
            ...order,
            rejector: fromLevel === 'level1' ? 'Trưởng phòng Kinh doanh' : 'Giám đốc',
            rejectDate: new Date().toISOString().split('T')[0],
            rejectReason: reason
        });
        
        orders[fromLevel] = orders[fromLevel].filter(o => o.id !== currentOrderId);
        
        loadAllData();
        closeRejectModal();
        alert('Đã từ chối đơn hàng!');
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
    if (event.target == document.getElementById('rejectModal')) {
        closeRejectModal();
    }
}
