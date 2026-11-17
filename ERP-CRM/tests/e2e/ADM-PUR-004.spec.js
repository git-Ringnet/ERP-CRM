import { test, expect } from '@playwright/test';
import { captureStep, loginAs, runFixture } from './helpers/e2e.js';

const TC_ID = 'ADM-PUR-004';

test.describe(TC_ID + ' - Duyệt đặt hàng: Bắt buộc lý do khi trả về & hiển thị note PO team khi duyệt lại', () => {
    let data;

    test.beforeAll(() => {
        runFixture('cleanup');
        data = runFixture('setup_adm_pur_004');
    });

    test.afterAll(() => {
        runFixture('cleanup');
    });

    test('Chặn trả về không nhập lý do và hiển thị lý do PO team trả về để Admin duyệt lại', async ({ page }) => {
        // 1. Admin đăng nhập và mở danh sách Duyệt yêu cầu (PR)
        await loginAs(page, data.adminEmail);
        await page.goto(`/purchase-requests?sale_code=${encodeURIComponent(data.saleCode)}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.saleCode);
        await captureStep(page, TC_ID, '01_admin_views_pending_request');

        // Mở modal trả về của Admin trên PR đang Chờ Admin duyệt
        const adminRejectBtn = page.locator(`button[onclick*="showAdminRejectModal('${data.sorId}'"]`);
        await expect(adminRejectBtn).toBeVisible();
        await adminRejectBtn.click();

        const adminRejectModal = page.locator('#adminRejectModal');
        await expect(adminRejectModal).toBeVisible();
        const reasonTextarea = adminRejectModal.locator('textarea[name="rejection_note"]');
        await expect(reasonTextarea).toHaveAttribute('required', '');
        await captureStep(page, TC_ID, '02_admin_reject_form_empty_blocked');

        // Bấm xác nhận khi chưa nhập lý do -> bị chặn bởi HTML5 required
        await adminRejectModal.locator('button[type="submit"]').click();
        const isInvalid = await reasonTextarea.evaluate((el) => !el.checkValidity());
        expect(isInvalid).toBe(true);
        await adminRejectModal.locator('button:has-text("Hủy")').click();

        // Admin duyệt yêu cầu để chuyển sang PO Team
        page.once('dialog', (dialog) => dialog.accept());
        const approveBtn = page.locator(`form[action*="/order-request/${data.sorId}/admin-approve"] button[type="submit"]`);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            approveBtn.click(),
        ]);
        await captureStep(page, TC_ID, '03_admin_approved_sent_to_po');

        // 2. PO Team đăng nhập và trả về PR kèm lý do
        await loginAs(page, data.poEmail);
        await page.goto(`/purchase-requests?sale_code=${encodeURIComponent(data.saleCode)}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.saleCode);
        await captureStep(page, TC_ID, '04_po_team_sees_pr');

        const poRejectBtn = page.locator(`button[onclick*="showRejectModal('${data.sorId}'"]`);
        await poRejectBtn.click();

        const poRejectModal = page.locator('#rejectModal');
        await expect(poRejectModal).toBeVisible();
        const poReason = 'PO Team: Thiếu file PO khách hàng ký xác nhận';
        await poRejectModal.locator('textarea[name="rejection_note"]').fill(poReason);
        await captureStep(page, TC_ID, '05_po_team_reject_with_reason');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            poRejectModal.locator('button[type="submit"]').click(),
        ]);

        // 3. Sales mở đơn hàng -> thấy lý do PO team -> bấm Chỉnh sửa & Gửi lại
        await loginAs(page, data.salesEmail);
        await page.goto(`/sales/${data.saleId}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(poReason);
        await captureStep(page, TC_ID, '06_sales_sees_po_reason');

        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            page.locator('a:has-text("Chỉnh sửa & Gửi lại")').first().click(),
        ]);
        const fileInput = page.locator('input[type="file"]').first();
        if (await fileInput.count() > 0) {
            await fileInput.setInputFiles({
                name: 'e2e-po.pdf',
                mimeType: 'application/pdf',
                buffer: Buffer.from('%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF'),
            });
        }
        // Chọn Vendor chung nếu chưa có để đảm bảo form hợp lệ
        const vendorSelect = page.locator('select').first();
        if (await vendorSelect.count() > 0) {
            const options = await vendorSelect.locator('option').count();
            if (options > 1) {
                await vendorSelect.selectOption({ index: 1 });
                const applyAllBtn = page.locator('button:has-text("Áp dụng cho tất cả hàng")');
                if (await applyAllBtn.isVisible()) {
                    await applyAllBtn.click();
                }
            }
        }
        const submitReviewBtn = page.locator('button:has-text("Cập nhật & Gửi duyệt lại"), button:has-text("Gửi yêu cầu đặt hàng")').first();
        await expect(submitReviewBtn).toBeVisible();
        await submitReviewBtn.click();

        const confirmSubmitBtn = page.locator('#confirmSubmitBtn');
        await expect(confirmSubmitBtn).toBeVisible();
        await Promise.all([
            page.waitForURL(/\/sales\/\d+/, { timeout: 15_000 }),
            confirmSubmitBtn.click(),
        ]);

        // 4. Admin mở lại danh sách PR & chi tiết đơn hàng để duyệt lại -> PHẢI THẤY note lý do PO Team đã trả về khi đang Chờ Admin duyệt
        await loginAs(page, data.adminEmail);
        await page.goto(`/purchase-requests?sale_code=${encodeURIComponent(data.saleCode)}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText('Chờ Admin duyệt');
        await expect(page.locator('body')).toContainText(poReason.slice(0, 20));
        await captureStep(page, TC_ID, '07_admin_sees_po_return_note_in_pr_list');

        await page.goto(`/sales/${data.saleId}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText('Chờ Admin duyệt');
        await expect(page.locator('body')).toContainText(poReason);
        await captureStep(page, TC_ID, '08_admin_sees_po_return_note_on_sale');
    });
});
