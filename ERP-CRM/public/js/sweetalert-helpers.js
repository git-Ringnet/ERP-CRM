/**
 * SweetAlert2 Helper Functions
 * Các hàm helper để xử lý approve/reject phiếu với SweetAlert2
 */

/**
 * Confirm và approve phiếu
 */
async function confirmApprove(url, documentName = 'phiếu') {
    const result = await Swal.fire({
        title: 'Xác nhận duyệt phiếu',
        text: `Bạn có chắc muốn duyệt ${documentName} này? Sau khi duyệt sẽ không thể chỉnh sửa.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#27ae60',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Duyệt phiếu',
        cancelButtonText: 'Hủy',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
                
                return data;
            } catch (error) {
                Swal.showValidationMessage(error.message);
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (result.isConfirmed) {
        await Swal.fire({
            title: 'Thành công!',
            text: result.value.message,
            icon: 'success',
            confirmButtonColor: '#27ae60'
        });
        window.location.reload();
    }
}

/**
 * Confirm và reject phiếu với lý do
 */
async function confirmReject(url, documentName = 'phiếu') {
    const result = await Swal.fire({
        title: 'Từ chối phiếu',
        text: `Vui lòng nhập lý do từ chối ${documentName}:`,
        input: 'textarea',
        inputPlaceholder: 'Nhập lý do từ chối (tối thiểu 5 ký tự)...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Từ chối',
        cancelButtonText: 'Hủy',
        inputValidator: (value) => {
            if (!value) {
                return 'Bạn cần nhập lý do từ chối!';
            }
            if (value.length < 5) {
                return 'Lý do phải có ít nhất 5 ký tự!';
            }
        },
        showLoaderOnConfirm: true,
        preConfirm: async (reason) => {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reason })
                });
                
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
                
                return data;
            } catch (error) {
                Swal.showValidationMessage(error.message);
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (result.isConfirmed) {
        await Swal.fire({
            title: 'Đã từ chối!',
            text: result.value.message,
            icon: 'success',
            confirmButtonColor: '#27ae60'
        });
        window.location.reload();
    }
}

/**
 * Confirm và xóa bản ghi
 */
async function confirmDelete(form, documentName = 'bản ghi') {
    const result = await Swal.fire({
        title: 'Xác nhận xóa',
        text: `Bạn có chắc chắn muốn xóa ${documentName} này không? Hành động này không thể hoàn tác!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c', // Màu đỏ cho nút xóa
        cancelButtonColor: '#95a5a6', // Màu xám cho nút hủy
        confirmButtonText: 'Xóa ngay',
        cancelButtonText: 'Hủy',
        reverseButtons: true // Đưa nút Hủy sang trái, Xóa sang phải
    });

    if (result.isConfirmed) {
        if (form instanceof HTMLFormElement) {
            HTMLFormElement.prototype.submit.call(form);
        } else if (typeof form === 'string') {
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = form;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').content;
            f.appendChild(csrf);
            document.body.appendChild(f);
            f.submit();
        } else {
            console.error('confirmDelete: Invalid form/URL provided', form);
        }
    }
}

/**
 * Confirm và xóa bản ghi với lý do
 */
async function confirmDeleteWithReason(form, documentName = 'bản ghi') {
    const result = await Swal.fire({
        title: 'Xác nhận xóa',
        text: `Bạn có chắc chắn muốn xóa ${documentName} này không? Hành động này không thể hoàn tác!`,
        input: 'text',
        inputPlaceholder: 'Nhập lý do xóa (không bắt buộc)...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Xóa ngay',
        cancelButtonText: 'Hủy',
        reverseButtons: true
    });

    if (result.isConfirmed) {
        let actualForm = form;
        if (typeof form === 'string') {
            actualForm = document.createElement('form');
            actualForm.method = 'POST';
            actualForm.action = form;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').content;
            actualForm.appendChild(csrf);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            actualForm.appendChild(methodInput);
            
            document.body.appendChild(actualForm);
        }
        
        // Add reason field
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'reason';
        reasonInput.value = result.value || '';
        actualForm.appendChild(reasonInput);
        
        if (actualForm instanceof HTMLFormElement) {
            HTMLFormElement.prototype.submit.call(actualForm);
        } else {
            console.error('confirmDeleteWithReason: Invalid form/URL provided', form);
        }
    }
}

/**
 * Confirm và thực hiện một hành động (submit form)
 */
async function confirmAction(form, title = 'Xác nhận', text = 'Bạn có chắc chắn muốn thực hiện hành động này?', icon = 'question', confirmButtonText = 'Đồng ý', confirmButtonColor = '#3085d6') {
    const result = await Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: confirmButtonColor,
        cancelButtonColor: '#95a5a6',
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'Hủy',
        reverseButtons: true
    });

    if (result.isConfirmed) {
        if (form instanceof HTMLFormElement) {
            HTMLFormElement.prototype.submit.call(form);
        } else if (typeof form === 'string') {
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = form;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').content;
            f.appendChild(csrf);
            document.body.appendChild(f);
            f.submit();
        } else {
            console.error('confirmAction: Invalid form/URL provided', form);
        }
    }
}
