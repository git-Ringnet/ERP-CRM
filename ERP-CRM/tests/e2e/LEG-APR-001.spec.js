import { test, expect } from '@playwright/test';
import { captureStep, loginAs, runFixture } from './helpers/e2e.js';

const TC_ID = 'LEG-APR-001';

test.describe(TC_ID + ' - Sales reply review note khi gửi lại & chỉ thông báo cho đúng account Legal đã review', () => {
    let data;

    test.beforeAll(() => {
        runFixture('cleanup');
        data = runFixture('setup_leg_bod_pnl');
    });

    test.afterAll(() => {
        runFixture('cleanup');
    });

    test('Sales reply review note khi gửi lại P&L và chỉ thông báo cho đúng Legal đã yêu cầu chỉnh sửa', async ({ page }) => {
        // 1. Legal 1 yêu cầu chỉnh sửa (need_revision)
        await loginAs(page, data.legal1Email);
        await page.goto(`/sales/${data.saleL1Id}#pnl`, { waitUntil: 'domcontentloaded' });
        await page.locator('button:has-text("Yêu cầu chỉnh sửa")').first().click();

        const revisionForm = page.locator('form[action*="request-revision-pnl"]');
        await expect(revisionForm).toBeVisible();
        const reviewNote = 'Legal 1: Cần bổ sung lại chứng từ giá vốn';
        await revisionForm.locator('textarea[name="comment"]').fill(reviewNote);
        await captureStep(page, TC_ID, '01_legal1_requests_revision');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            revisionForm.locator('button[type="submit"]').click(),
        ]);

        // 2. Sales mở tab P&L, bấm "Gửi duyệt lại P&L", nhập reply trong ô phản hồi
        await loginAs(page, data.salesEmail);
        await page.goto(`/sales/${data.saleL1Id}#pnl`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(reviewNote);

        await page.locator('button:has-text("Gửi duyệt lại P&L")').click();
        const replyTextarea = page.locator('#pnl_submit_comment');
        await expect(replyTextarea).toBeVisible();
        const salesReply = 'Đã cập nhật giá vốn và bổ sung file theo ghi chú';
        await replyTextarea.fill(salesReply);
        await captureStep(page, TC_ID, '02_sales_enters_reply_note');

        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            page.locator('.swal2-confirm').click(),
        ]);
        await expect(page.locator('body')).toContainText(salesReply);
        await captureStep(page, TC_ID, '03_sales_resubmitted_with_reply');

        // 3. Kiểm tra thông báo: CHỈ Legal 1 nhận thông báo, Legal 2 KHÔNG nhận
        const legal1Notifs = runFixture('check_notifications', { userId: data.legal1Id });
        const legal2Notifs = runFixture('check_notifications', { userId: data.legal2Id });
        expect(legal1Notifs.items.some((n) => n.type === 'approval_pending' && n.message.includes(data.saleL1Code))).toBe(true);
        expect(legal2Notifs.items.some((n) => n.type === 'approval_pending' && n.message.includes(data.saleL1Code))).toBe(false);

        // 4. Legal 1 mở lại đơn hàng -> thấy ngay Phản hồi Sales ở cả header và trong Lịch sử duyệt P&L
        await loginAs(page, data.legal1Email);
        await page.goto(`/sales/${data.saleL1Id}#pnl`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText('Phản hồi Sales:');
        await expect(page.locator('body')).toContainText('Phản hồi từ Sales');
        await expect(page.locator('body')).toContainText(salesReply);
        await captureStep(page, TC_ID, '04_legal1_sees_sales_reply');
    });
});
