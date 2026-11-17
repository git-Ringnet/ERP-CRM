import { test, expect } from '@playwright/test';
import { captureStep, loginAs, runFixture } from './helpers/e2e.js';

const TC_ID = 'ADM-OUT-002';

test.describe(TC_ID + ' - Hàng runrate giữ thiếu suất: Chặn gửi đề xuất xuất kho và báo lỗi', () => {
    let data;

    test.beforeAll(() => {
        runFixture('cleanup');
        data = runFixture('setup_adm_out_002');
    });

    test.afterAll(() => {
        runFixture('cleanup');
    });

    test('Không gửi được đề xuất xuất kho khi giữ thiếu suất và hiển thị thông báo "chỉ đang giữ x/y"', async ({ page }) => {
        await loginAs(page, data.salesEmail);

        // 1. Gửi duyệt từ trang chi tiết đơn hàng (tab Xuất kho)
        await page.goto(`/sales/${data.saleId}#warehouse`, { waitUntil: 'domcontentloaded' });
        await captureStep(page, TC_ID, '01_sales_warehouse_tab_draft_export');

        page.once('dialog', (dialog) => dialog.accept());
        const sendApproveBtn = page.locator(`form[action*="/exports/${data.exportId}/request-export"] button[type="submit"]`);
        await expect(sendApproveBtn).toBeVisible();
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            sendApproveBtn.click(),
        ]);

        const errorAlert = page.locator('[role="alert"]').filter({ hasText: 'chỉ đang giữ 1/3' });
        await expect(errorAlert).toBeVisible();
        await captureStep(page, TC_ID, '02_blocked_with_hold_shortage_alert');
    });
});
