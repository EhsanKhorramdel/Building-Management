import { defineConfig } from '@playwright/test';
import { environments, php } from './tests/e2e/environment.js';

export default defineConfig({
    testDir: 'tests/e2e',
    globalSetup: './tests/e2e/global-setup.js',
    outputDir: 'test-results',
    workers: 1,
    timeout: 150_000,
    expect: { timeout: 8_000 },
    reporter: [['list'], ['json', { outputFile: 'playwright-report/results.json' }]],
    use: {
        browserName: 'chromium',
        // Use installed Chrome on Windows while the managed Chromium download is unavailable here.
        channel: process.env.PLAYWRIGHT_CHANNEL === 'chromium' ? undefined : process.env.PLAYWRIGHT_CHANNEL || (process.platform === 'win32' ? 'chrome' : undefined),
        baseURL: environments.manual.url,
        viewport: { width: 1440, height: 900 },
        actionTimeout: 12_000,
        navigationTimeout: 60_000,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: Object.values(environments).map(({ app, url, port, env }) => ({
        command: `"${php}" artisan serve --host=127.0.0.1 --port=${port} --tries=1 --no-reload`,
        cwd: app,
        url,
        env,
        reuseExistingServer: false,
        timeout: 180_000,
    })),
});
