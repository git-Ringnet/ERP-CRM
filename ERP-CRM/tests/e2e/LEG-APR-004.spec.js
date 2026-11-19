import { test, expect } from '@playwright/test';
import { captureStep, loginAs, runFixture } from './helpers/e2e.js';

const TC_ID = 'LEG-APR-004';

test.describe(TC_ID + ' - Legal thấy menu Xuất kho và mở xem chi tiết phiếu xuất kho của đơn hàng', () => {
    let data;

    test.beforeAll(() => {
        runFixture('cleanup');
        data = runFixture('setup_leg_bod_pnl');
    });

    test.afterAll(() => {
        runFixture('cleanup');
    });

    test('Tài khoản Legal thấy menu Xuất kho, xem được từ tab Kho & Giao nhận của đơn hàng và mở được chi tiết phiếu xuất', async ({ page }) => {
        await loginAs(page, data.legal1Email);

        // 1. Thấy menu Kho hàng -> Xuất kho trên sidebar và vào được /exports
        await page.goto('/sales', { waitUntil: 'domcontentloaded' });
        const exportMenuLink = page.locator('a[href$="/exports"]');
        await expect(exportMenuLink).toBeAttached();
        await page.goto('/exports', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).toContainText(data.exportCode);
        await captureStep(page, TC_ID, '01_legal_sees_exports_index');

        // 2. Mở từ tab Kho & Giao nhận trên đơn hàng
        await page.goto(`/sales/${data.saleL1Id}#warehouse`, { waitUntil: 'domcontentloaded' });
        const exportLinkOnSale = page.locator(`a[href$="/exports/${data.exportId}"]`);
        await expect(exportLinkOnSale).toBeVisible();
        await captureStep(page, TC_ID, '02_legal_sees_export_link_on_sale');

        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            exportLinkOnSale.click(),
        ]);
        await expect(page.locator('body')).toContainText(data.exportCode);
        await expect(page.locator('body')).toContainText('Chi tiết Phiếu Xuất Kho');
        await captureStep(page, TC_ID, '03_legal_views_export_detail');
    });
});
