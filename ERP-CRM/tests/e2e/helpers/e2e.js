import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { expect } from '@playwright/test';

const SCREENSHOT_ROOT = path.resolve(process.cwd(), 'tests', 'e2e', 'screenshots');
const FIXTURE_SCRIPT = path.resolve(process.cwd(), 'tests', 'e2e', 'scripts', 'e2e_fixture.php');

/**
 * Run the PHP fixture helper and return parsed JSON.
 */
export function runFixture(action, payload = {}) {
    const raw = execFileSync(
        'php',
        [FIXTURE_SCRIPT, action, JSON.stringify(payload)],
        { encoding: 'utf8', cwd: process.cwd() }
    );
    const lines = raw.trim().split(/\r?\n/);
    const lastLine = lines[lines.length - 1];
    return JSON.parse(lastLine);
}

/**
 * Capture a full-page screenshot for a test case step.
 */
export async function captureStep(page, tcId, stepName) {
    const dir = path.join(SCREENSHOT_ROOT, tcId);
    fs.mkdirSync(dir, { recursive: true });
    const safeName = stepName.replace(/[^a-zA-Z0-9_-]+/g, '_');
    const filePath = path.join(dir, `${safeName}.png`);
    await page.screenshot({ path: filePath, fullPage: true });
    return filePath;
}

/**
 * Log in via the web login form.
 */
export async function loginAs(page, email, password = 'E2ePassword123!') {
    const gotoLogin = async () => {
        try {
            await page.goto('/login', { waitUntil: 'domcontentloaded' });
        } catch (e) {
            if (!String(e.message || e).includes('ERR_ABORTED')) throw e;
            await page.waitForTimeout(300);
            await page.goto('/login', { waitUntil: 'domcontentloaded' });
        }
    };
    await gotoLogin();
    // Clear existing session if already logged in and redirected away from /login
    if (!page.url().includes('/login')) {
        await page.context().clearCookies();
        await gotoLogin();
    }
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    try {
        await Promise.all([
            page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 15_000 }),
            page.click('button[type="submit"]'),
        ]);
    } catch (e) {
        if (!String(e.message || e).includes('ERR_ABORTED')) throw e;
        await page.waitForTimeout(300);
        await page.goto('/', { waitUntil: 'domcontentloaded' });
    }
    await expect(page.locator('body')).toBeVisible();
}
