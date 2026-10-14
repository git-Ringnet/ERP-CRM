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

/**
 * Confirm và hủy đơn mua hàng (PO) với lý do để revert yêu cầu đặt hàng về Sales ở trạng thái "Thiếu thông tin"
 */
async function confirmCancelPo(form) {
    if (typeof Swal === 'undefined') {
        const reason = prompt('Vui lòng nhập lý do hủy đặt hàng với hãng và hoàn trả về cho Sales:');
        if (reason !== null && reason.trim() !== '') {
            let reasonInput = form.querySelector('input[name="reason"]');
            if (!reasonInput) {
                reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'reason';
                form.appendChild(reasonInput);
            }
            reasonInput.value = reason.trim();
            form.submit();
        }
        return;
    }

    const result = await Swal.fire({
        title: 'Hủy Đơn Đặt Hàng với Hãng',
        html: `
            <div style="text-align: left; font-size: 13px; line-height: 1.5; color: #4b5563; margin-bottom: 12px;">
                Khi hủy đơn hàng này, toàn bộ sản phẩm và yêu cầu đặt hàng (PR) liên quan sẽ 
                <strong style="color: #b45309;">hoàn trả trực tiếp về cho Sales ở trạng thái "Thiếu thông tin"</strong>.
                Đơn đặt hàng được giữ lại: sau khi Sales chỉnh sửa và yêu cầu đặt hàng được duyệt lại, sản phẩm
                <strong style="color: #047857;">tự quay về đơn này để xử lý tiếp</strong> (không tạo đơn đặt hàng mới).
            </div>
            <div style="text-align: left; margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px;">Hình thức xử lý khi hủy:</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px;">
                    <label style="display: flex; align-items: flex-start; gap: 6px; padding: 8px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
                        <input type="radio" name="swal_po_cancel_target" value="order_request" checked style="margin-top: 2px;">
                        <div>
                            <div style="font-weight: 600; color: #1f2937;">Chỉnh lại yêu cầu đặt hàng</div>
                            <div style="font-size: 11px; color: #6b7280;">Sales chỉnh sửa lại yêu cầu đặt hàng và gửi lại</div>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 6px; padding: 8px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
                        <input type="radio" name="swal_po_cancel_target" value="pnl" style="margin-top: 2px;">
                        <div>
                            <div style="font-weight: 600; color: #b91c1c;">Chỉnh lại P&L</div>
                            <div style="font-size: 11px; color: #6b7280;">Mở lại P&L cho Sales chỉnh sửa chi phí, duyệt lại P&L trước khi đặt hàng</div>
                        </div>
                    </label>
                </div>
            </div>
            <div style="text-align: left;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #4b5563; margin-bottom: 4px;">Lý do hủy đơn & yêu cầu bổ sung thông tin (bắt buộc):</label>
                <textarea id="swal_po_cancel_reason" rows="3" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; font-size: 12px; box-sizing: border-box;" placeholder="Nhập lý do chi tiết (VD: Hãng báo hết hàng, sai part number, thay đổi thông số/chi phí...)..."></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: '<i class="fas fa-undo-alt mr-1"></i> Xác nhận hủy & Trả về',
        cancelButtonText: 'Đóng',
        reverseButtons: true,
        preConfirm: () => {
            const reason = document.getElementById('swal_po_cancel_reason')?.value?.trim();
            const target = document.querySelector('input[name="swal_po_cancel_target"]:checked')?.value || 'order_request';
            if (!reason || reason.length < 3) {
                Swal.showValidationMessage('Vui lòng nhập lý do hủy đơn (tối thiểu 3 ký tự)!');
                return false;
            }
            return { reason: reason, cancel_target: target };
        }
    });

    if (result.isConfirmed && result.value) {
        let reasonInput = form.querySelector('input[name="reason"]');
        if (!reasonInput) {
            reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            form.appendChild(reasonInput);
        }
        reasonInput.value = result.value.reason;

        let targetInput = form.querySelector('input[name="cancel_target"]');
        if (!targetInput) {
            targetInput = document.createElement('input');
            targetInput.type = 'hidden';
            targetInput.name = 'cancel_target';
            form.appendChild(targetInput);
        }
        targetInput.value = result.value.cancel_target;

        HTMLFormElement.prototype.submit.call(form);
    }
}

/**
 * Confirm xóa cascade toàn bộ đơn hàng dành cho Quản trị viên
 */
async function confirmAdminCascadeDelete(form, saleCode = '') {
    if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
            title: '⚠️ CẢNH BÁO XÓA TOÀN BỘ (ADMIN)',
            html: `
                <div style="text-align: left; font-size: 13.5px; line-height: 1.6; color: #374151;">
                    <p style="font-weight: 600; color: #dc2626; margin-bottom: 8px;">
                        Bạn đang chuẩn bị xóa đơn hàng <strong>${saleCode}</strong> ở cấp độ Quản trị viên!
                    </p>
                    <p style="margin-bottom: 6px;">Hệ thống sẽ <strong>xóa hoàn toàn</strong> các dữ liệu liên quan sau:</p>
                    <ul style="list-style-type: disc; padding-left: 20px; margin-bottom: 10px; color: #4b5563; font-size: 13px;">
                        <li>Yêu cầu đặt hàng (PR) & Gom đơn cần đặt</li>
                        <li>Lịch sử phê duyệt đơn hàng & P&L</li>
                        <li>Đơn đặt hàng với hãng (PO) của đơn</li>
                        <li>Phiếu nhập kho & xuất kho liên quan</li>
                        <li>Khôi phục / điều chỉnh tồn kho và số serial</li>
                        <li>Yêu cầu xuất hóa đơn, lịch sử thanh toán công nợ</li>
                    </ul>
                    <p style="font-weight: 700; color: #b91c1c; border-top: 1px dashed #fca5a5; padding-top: 6px; margin-bottom: 0;">
                        Thao tác này mang tính rủi ro cao và KHÔNG THỂ HOÀN TÁC!
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Tôi hiểu rủi ro, Xóa toàn bộ',
            cancelButtonText: 'Hủy bỏ',
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
            }
        }
    } else {
        if (confirm(`CẢNH BÁO QUẢN TRỊ: Bạn có chắc chắn muốn xóa đơn hàng ${saleCode} và TOÀN BỘ dữ liệu liên quan (PR, gom đơn, PO, nhập/xuất kho, tồn kho)? Hành động này không thể hoàn tác!`)) {
            if (form instanceof HTMLFormElement) {
                form.submit();
            }
        }
    }
}
