import { test, expect } from '@playwright/test';
import { captureStep, loginAs, runFixture } from './helpers/e2e.js';

const TC_ID = 'LEG-APR-004';

test.describe(TC_ID + ' - Legal mở xem chi tiết phiếu xuất kho của đơn hàng', () => {
    let data;

    test.beforeAll(() => {
        runFixture('cleanup');
        data = runFixture('setup_leg_bod_pnl');
    });

    test.afterAll(() => {
        runFixture('cleanup');
    });

    test('Tài khoản Legal mở được trang chi tiết phiếu xuất kho của đơn', async ({ page }) => {
        await loginAs(page, data.legal1Email);
        const res = await page.goto(`/exports/${data.exportId}`, { waitUntil: 'domcontentloaded' });
        expect(res.status()).toBe(200);
        await expect(page.locator('body')).toContainText(data.exportCode);
        await expect(page.locator('body')).toContainText('Chi tiết Phiếu Xuất Kho');
        await captureStep(page, TC_ID, '01_legal_views_export_detail');
    });
});
