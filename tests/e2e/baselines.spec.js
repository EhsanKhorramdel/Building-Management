import { expect, test } from '@playwright/test';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';
import { seed } from './global-setup.js';
import { login, openBuilding, readyForCapture, tab, theme } from './ui.js';

const baselineDir = fileURLToPath(new URL('./baselines/', import.meta.url));
const output = 'storage/app/test-screenshots/phase-9/baselines';
const refresh = process.env.E2E_REFRESH_BASELINES === '1';
const channelThreshold = 0.15;
const pixelBudget = 0.005;

test.beforeAll(async () => {
    test.setTimeout(240_000);
    seed('manual');
    await mkdir(refresh ? baselineDir : output, { recursive: true });
});

function pad(image, width, height) {
    const padded = new PNG({ width, height });
    PNG.bitblt(image, padded, 0, 0, image.width, image.height, 0, 0);
    return padded.data;
}

for (const [state, width, dark] of [['dashboard', 1440, false], ['dashboard', 1440, true], ['chat', 390, true], ['home', 768, false]]) {
    const name = `${state}-${width}-${dark ? 'dark' : 'light'}`;
    test(name, async ({ browser, baseURL }, info) => {
        const context = await browser.newContext({
            baseURL, viewport: { width, height: width < 768 ? 820 : 900 }, hasTouch: width <= 768,
        });
        try {
            await theme(context, baseURL, dark);
            const page = await context.newPage();
            await login(page, 'manager');
            if (state !== 'dashboard') await openBuilding(page);
            if (state === 'chat') {
                await tab(page, 'chat');
                const latest = page.getByRole('button', { name: 'رفتن به آخرین پیام' });
                await latest.click();
                await expect(latest).toBeEnabled();
                await expect(page.getByText('پیام نمونه 36', { exact: true })).toBeInViewport();
                await expect.poll(() => page.locator('.chatContainer').evaluate(box => box.scrollHeight - box.clientHeight - box.scrollTop)).toBeLessThanOrEqual(1);
            }
            if (state === 'home') await expect(page.locator('.home-window .leaflet-marker-icon')).toBeVisible();
            await readyForCapture(page);
            const actual = await page.screenshot({ animations: 'disabled', caret: 'hide' });
            const baseline = join(baselineDir, name + '.png');
            if (refresh) {
                await writeFile(baseline, actual);
                return;
            }

            await writeFile(join(output, name + '-actual.png'), actual);
            const reference = await readFile(baseline).catch(error => {
                if (error.code === 'ENOENT') return null;
                throw error;
            });
            let result = { name, status: 'missing baseline' };
            if (reference) {
                const before = PNG.sync.read(reference);
                const after = PNG.sync.read(actual);
                const width = Math.max(before.width, after.width);
                const height = Math.max(before.height, after.height);
                const diff = new PNG({ width, height });
                const pixels = pixelmatch(pad(before, width, height), pad(after, width, height), diff.data, width, height, { threshold: channelThreshold });
                const ratio = pixels / (width * height);
                const sizeChanged = before.width !== after.width || before.height !== after.height;
                result = { name, pixels, ratio, sizeChanged, status: sizeChanged || ratio > pixelBudget ? 'different' : 'within tolerance' };
                const diffPath = join(output, name + '-diff.png');
                await writeFile(diffPath, PNG.sync.write(diff));
                if (result.status === 'different') await info.attach('pixel diff', { path: diffPath });
            }
            await writeFile(join(output, name + '.json'), JSON.stringify(result, null, 2));
            await info.attach('comparison', { body: JSON.stringify(result), contentType: 'application/json' });
        } finally {
            await context.close();
        }
    });
}
