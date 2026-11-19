import { test, expect } from '@playwright/test';
import { captureStep, loginAs, runFixture } from './helpers/e2e.js';

const TC_ID = 'SYS-PERM-003';

test.describe(TC_ID + ' - Phân quyền 3 mức xem own / group / all cho Báo giá, Đơn hàng, PO', () => {
    let data;

    test.beforeAll(() => {
        runFixture('cleanup');
        data = runFixture('setup_sys_perm_003');
    });

    test.afterAll(() => {
        runFixture('cleanup');
    });

    test('Mỗi mức own / group / all thấy đúng phạm vi trên Báo giá, Đơn hàng và PO', async ({ page }) => {
        // --- 1. Mức OWN ---
        await loginAs(page, data.ownEmail);

        await page.goto('/quotations', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.quotes.own);
        await expect(page.locator('body')).not.toContainText(data.quotes.group);
        await expect(page.locator('body')).not.toContainText(data.quotes.other);
        await captureStep(page, TC_ID, '01_own_quotations');

        await page.goto('/sales?search=E2E-S-', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.sales.own);
        await expect(page.locator('body')).not.toContainText(data.sales.group);
        await expect(page.locator('body')).not.toContainText(data.sales.other);
        await captureStep(page, TC_ID, '02_own_sales');

        await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.pos.own);
        await expect(page.locator('body')).not.toContainText(data.pos.group);
        await expect(page.locator('body')).not.toContainText(data.pos.other);
        await captureStep(page, TC_ID, '03_own_purchase_orders');

        // --- 2. Mức GROUP ---
        await loginAs(page, data.groupEmail);

        await page.goto('/quotations', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.quotes.own);
        await expect(page.locator('body')).toContainText(data.quotes.group);
        await expect(page.locator('body')).not.toContainText(data.quotes.other);
        await captureStep(page, TC_ID, '04_group_quotations');

        await page.goto('/sales?search=E2E-S-', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.sales.own);
        await expect(page.locator('body')).toContainText(data.sales.group);
        await expect(page.locator('body')).not.toContainText(data.sales.other);
        await captureStep(page, TC_ID, '05_group_sales');

        await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.pos.own);
        await expect(page.locator('body')).toContainText(data.pos.group);
        await expect(page.locator('body')).not.toContainText(data.pos.other);
        await captureStep(page, TC_ID, '06_group_purchase_orders');

        // --- 3. Mức ALL ---
        await loginAs(page, data.allEmail);

        await page.goto('/quotations', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.quotes.own);
        await expect(page.locator('body')).toContainText(data.quotes.group);
        await expect(page.locator('body')).toContainText(data.quotes.other);
        await captureStep(page, TC_ID, '07_all_quotations');

        await page.goto('/sales?search=E2E-S-', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.sales.own);
        await expect(page.locator('body')).toContainText(data.sales.group);
        await expect(page.locator('body')).toContainText(data.sales.other);
        await captureStep(page, TC_ID, '08_all_sales');

        await page.goto('/purchase-orders', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.pos.own);
        await expect(page.locator('body')).toContainText(data.pos.group);
        await expect(page.locator('body')).toContainText(data.pos.other);
        await captureStep(page, TC_ID, '09_all_purchase_orders');
    });
});
