let data = [
    { id: 1, code: 'NCC001', name: 'Nhà cung cấp A', email: 'supplier-a@email.com', phone: '0923456789', address: 'Hà Nội', taxCode: '0111222333', website: 'supplier-a.com', contactPerson: 'Nguyễn Văn A', paymentTerms: 30, productType: 'Điện tử', note: '' },
    { id: 2, code: 'NCC002', name: 'Nhà cung cấp B', email: 'supplier-b@email.com', phone: '0934567890', address: 'TP.HCM', taxCode: '0222333444', website: 'supplier-b.com', contactPerson: 'Trần Thị B', paymentTerms: 15, productType: 'Văn phòng phẩm', note: '' }
];

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupSearch();
});

function loadData() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    data.forEach(item => {
        const stars = '⭐'.repeat(item.rating);
        tbody.innerHTML += `
            <tr>
                <td><strong>${item.code}</strong></td>
                <td>${item.name}</td>
                <td>${item.email}</td>
                <td>${item.phone}</td>
                <td>${item.address}</td>
                <td>
                    <button class="btn-icon btn-view" onclick="viewData(${item.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit" onclick="editData(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" onclick="deleteData(${item.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
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
    document.getElementById('modalTitle').textContent = 'Thêm nhà cung cấp mới';
    document.getElementById('dataForm').reset();
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function viewData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        alert(`Thông tin nhà cung cấp:\n\nMã: ${item.code}\nTên: ${item.name}\nEmail: ${item.email}\nĐiện thoại: ${item.phone}\nĐịa chỉ: ${item.address}\nĐánh giá: ${'⭐'.repeat(item.rating)}\nĐiều khoản TT: ${item.paymentTerms} ngày`);
    }
}

function editData(id) {
    const item = data.find(d => d.id === id);
    if (item) {
        editingId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa nhà cung cấp';
        const form = document.getElementById('dataForm');
        form.elements['id'].value = item.id;
        form.elements['code'].value = item.code;
        form.elements['name'].value = item.name;
        form.elements['email'].value = item.email;
        form.elements['phone'].value = item.phone;
        form.elements['address'].value = item.address;
        form.elements['rating'].value = item.rating;
        form.elements['taxCode'].value = item.taxCode || '';
        form.elements['website'].value = item.website || '';
        form.elements['contactPerson'].value = item.contactPerson || '';
        form.elements['paymentTerms'].value = item.paymentTerms || 30;
        form.elements['productType'].value = item.productType || '';
        form.elements['note'].value = item.note || '';
        document.getElementById('modal').style.display = 'block';
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
        email: formData.get('email'),
        phone: formData.get('phone'),
        address: formData.get('address'),
        rating: parseInt(formData.get('rating')),
        taxCode: formData.get('taxCode'),
        website: formData.get('website'),
        contactPerson: formData.get('contactPerson'),
        paymentTerms: parseInt(formData.get('paymentTerms')),
        productType: formData.get('productType'),
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

function exportData() {
    alert('Chức năng xuất Excel đang được phát triển');
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) {
        closeModal();
    }
}
