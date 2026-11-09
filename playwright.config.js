import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: 'tests/Browser',
    outputDir: 'storage/app/test-screenshots/results',
    workers: 1,
    timeout: 180_000,
    use: {
        browserName: 'chromium',
        channel: process.env.PLAYWRIGHT_CHANNEL || undefined,
        baseURL: process.env.VISUAL_BASE_URL || 'http://127.0.0.1:8155',
    },
});
