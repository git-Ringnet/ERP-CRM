import { test, expect } from '@playwright/test';
import { captureStep, loginAs, runFixture } from './helpers/e2e.js';

const TC_ID = 'LEG-REC-001_BOD-PNL2-001';

test.describe(TC_ID + ' - Phân loại/Sort P&L cấp 1 & cấp 2, và BOD nhận thông báo duyệt cấp 2', () => {
    let data;

    test.beforeAll(() => {
        runFixture('cleanup');
        data = runFixture('setup_leg_bod_pnl');
    });

    test.afterAll(() => {
        runFixture('cleanup');
    });

    test('Phân loại riêng cấp 1 / cấp 2, sort ưu tiên trên Nháp, và BOD nhận thông báo duyệt cấp 2', async ({ page }) => {
        // 1. Legal xem danh sách đơn hàng -> thấy "Chờ duyệt PNL cấp 1" và đơn cấp 1 nằm trên đơn Nháp
        await loginAs(page, data.legal1Email);
        await page.goto('/sales?search=E2E-SO-', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText('Chờ duyệt PNL cấp 1');
        await expect(page.locator('body')).toContainText('Chờ duyệt PNL cấp 2');

        const bodyTextLegal = await page.locator('tbody').innerText();
        expect(bodyTextLegal.indexOf(data.saleL1Code)).toBeLessThan(bodyTextLegal.indexOf(data.saleDraftCode));
        await captureStep(page, TC_ID, '01_legal_sees_level1_sorted_above_draft');

        // Lọc riêng "Chờ duyệt PNL cấp 1"
        await page.goto('/sales?status=pnl_pending_level1&search=E2E-SO-', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.saleL1Code);
        await expect(page.locator('body')).not.toContainText(data.saleL2Code);
        await captureStep(page, TC_ID, '02_filter_level1_only');

        // Lọc riêng "Chờ duyệt PNL cấp 2"
        await page.goto('/sales?status=pnl_pending_level2&search=E2E-SO-', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.saleL2Code);
        await expect(page.locator('body')).not.toContainText(data.saleL1Code);
        await captureStep(page, TC_ID, '03_filter_level2_only');

        // 2. Legal duyệt cấp 1 trên saleL1 -> BOD phải nhận thông báo duyệt cấp 2
        await page.goto(`/sales/${data.saleL1Id}#pnl`, { waitUntil: 'domcontentloaded' });
        const approveFormBtn = page.locator('#approveForm button[type="submit"]');
        await expect(approveFormBtn).toBeVisible();
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            approveFormBtn.click(),
        ]);
        await captureStep(page, TC_ID, '04_legal_approved_level1');

        const bodNotifs = runFixture('check_notifications', { userId: data.bodId });
        expect(bodNotifs.items.some((n) => n.type === 'approval_pending' && n.message.includes(data.saleL1Code))).toBe(true);

        // 3. BOD đăng nhập -> thấy saleL1 & saleL2 ở trên Nháp, mở saleL1 duyệt cấp 2 -> Đã duyệt hoàn tất
        await loginAs(page, data.bodEmail);
        await page.goto('/sales?search=E2E-SO-', { waitUntil: 'domcontentloaded' });
        const bodyTextBod = await page.locator('tbody').innerText();
        expect(bodyTextBod.indexOf(data.saleL1Code)).toBeLessThan(bodyTextBod.indexOf(data.saleDraftCode));
        await captureStep(page, TC_ID, '05_bod_sees_level2_sorted_above_draft');

        await page.goto(`/sales/${data.saleL1Id}#pnl`, { waitUntil: 'domcontentloaded' });
        const bodApproveBtn = page.locator('#approveForm button[type="submit"]');
        await expect(bodApproveBtn).toBeVisible();
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            bodApproveBtn.click(),
        ]);
        await expect(page.locator('body')).toContainText('Đã duyệt hoàn tất');
        await captureStep(page, TC_ID, '06_bod_approved_level2_completed');

        const salesNotifs = runFixture('check_notifications', { userId: data.salesId });
        expect(salesNotifs.items.some((n) => n.type === 'approval_approved' && n.message.includes(data.saleL1Code))).toBe(true);
    });
});
