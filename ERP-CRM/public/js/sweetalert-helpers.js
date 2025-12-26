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
