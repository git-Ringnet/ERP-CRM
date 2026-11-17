import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 60_000,
    expect: {
        timeout: 10_000,
    },
    reporter: [
        ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ],
    use: {
        baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:8010',
        viewport: { width: 1440, height: 900 },
        screenshot: 'on',
        trace: 'retain-on-failure',
        video: 'retain-on-failure',
        actionTimeout: 15_000,
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8010',
        url: 'http://127.0.0.1:8010/login',
        reuseExistingServer: true,
        timeout: 30_000,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
