import { test, expect } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';

const output = 'storage/app/test-screenshots/phase-7';
const blankTile = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR4nGNgAAIAAAUAAaXWZZ0AAAAASUVORK5CYII=', 'base64');

async function openTab(page, tab, width) {
    if (width < 768) await page.locator('[data-open-drawer]').click();
    await page.locator('[data-tab="' + tab + '"]').click();
}

for (const width of [390, 1440]) {
    for (const theme of ['light', 'dark']) {
        test('location picker and home map at ' + width + 'px in ' + theme, async ({ page, baseURL }) => {
            test.setTimeout(60_000);
            await mkdir(output, { recursive: true });
            await page.setViewportSize({ width, height: width < 768 ? 820 : 900 });
            await page.context().addCookies([{
                name: 'userSettings',
                value: encodeURIComponent(JSON.stringify({ darkMode: theme === 'dark', viewMode: 'gridView' })),
                url: baseURL,
            }]);

            const offline = process.env.MAP_VISUAL_OFFLINE === '1';
            if (offline) {
                await page.route('https://tile.openstreetmap.org/**', route => route.fulfill({
                    contentType: 'image/png', body: blankTile,
                }));
            }

            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            page.on('console', message => {
                if (message.type() === 'error') errors.push(message.text());
            });
            await page.goto('/login');
            await page.locator('[name=email]').fill(process.env.DEMO_MANAGER_EMAIL);
            await page.locator('[name=password]').fill(process.env.DEMO_PASSWORD);
            await page.locator('form button[type=submit]').first().click({ noWaitAfter: true });
            await page.waitForURL(url => url.pathname === '/dashboard', { waitUntil: 'domcontentloaded' });
            const building = await page.locator('a[href*="/dashboard/building/"]').first().getAttribute('href');
            await page.goto(building, { waitUntil: 'domcontentloaded' });
            await openTab(page, 'setting', width);

            const picker = page.locator('[data-coordinate-picker]');
            const canvas = picker.locator('[data-complex-map]');
            const marker = canvas.locator('.leaflet-marker-icon');
            const latitude = picker.locator('[name=latitude]');
            const longitude = picker.locator('[name=longitude]');

            await picker.locator('[data-map-clear]').click();
            await expect(latitude).toHaveValue('');
            await expect(longitude).toHaveValue('');
            await expect(marker).toHaveCount(0);
            await canvas.click({ position: { x: 145, y: 120 } });
            await expect(marker).toBeVisible();
            await expect(latitude).not.toHaveValue('');
            await expect(longitude).not.toHaveValue('');

            if (width === 390 && theme === 'light') {
                const beforeDrag = await latitude.inputValue();
                await marker.scrollIntoViewIfNeeded();
                const box = await marker.boundingBox();
                await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
                await page.mouse.down();
                await page.mouse.move(box.x + box.width / 2 + 20, box.y + box.height / 2 + 25, { steps: 5 });
                await page.mouse.up();
                await expect(latitude).not.toHaveValue(beforeDrag);
            }

            await latitude.fill('35.7009');
            await longitude.fill('51.3914');
            await expect(marker).toBeVisible();

            const guard = [];
            async function capture(name, map) {
                await map.scrollIntoViewIfNeeded();
                await expect(map.locator('.leaflet-control-zoom')).toBeVisible();
                if (width < 768) {
                    const zoom = await map.locator('.leaflet-control-zoom-in').boundingBox();
                    expect(zoom.width).toBeGreaterThanOrEqual(40);
                    expect(zoom.height).toBeGreaterThanOrEqual(40);
                }
                await expect(map.locator('.leaflet-control-attribution')).toContainText('OpenStreetMap');
                await expect.poll(() => map.locator('.leaflet-marker-icon').evaluate(icon => icon.complete && icon.naturalWidth > 0)).toBe(true);
                await expect(map).toHaveAttribute('dir', 'ltr');
                await page.evaluate(() => document.fonts.ready);
                await expect(map).not.toHaveClass(/leaflet-zoom-anim/);
                await page.waitForFunction(element => {
                    const tiles = [...element.querySelectorAll('.leaflet-tile')];
                    return tiles.length > 0 && tiles.every(tile =>
                        tile.classList.contains('leaflet-tile-loaded') && Number(getComputedStyle(tile).opacity) >= 0.99
                    );
                }, await map.elementHandle(), { timeout: 10000 }).catch(() => {});
                await page.screenshot({ path: output + '/' + name + '-' + width + '-' + theme + '.png', animations: 'disabled', timeout: 10000 });
                const metrics = await map.evaluate(element => ({
                    width: innerWidth,
                    scrollWidth: document.documentElement.scrollWidth,
                    height: element.getBoundingClientRect().height,
                    dark: document.documentElement.classList.contains('dark'),
                    tiles: element.querySelectorAll('.leaflet-tile-loaded').length,
                }));
                guard.push({ name, ...metrics, errors: [...errors], offline });
                expect(metrics.scrollWidth).toBeLessThanOrEqual(width);
                expect(metrics.height).toBe(width < 768 ? 260 : 320);
                expect(metrics.dark).toBe(theme === 'dark');
            }

            await capture('settings-picker', canvas);
            await Promise.all([
                page.waitForEvent('domcontentloaded'),
                picker.locator('..').locator('button[type=submit]').click({ noWaitAfter: true }),
            ]);
            await expect(page.locator('.setting-window')).toBeVisible();
            await expect(page.locator('.setting-window [name=latitude]')).toHaveValue('35.7009');
            await openTab(page, 'home', width);
            const home = page.locator('.home-window [data-complex-map]');
            await expect(home).toHaveAttribute('data-latitude', '35.7009');
            await expect(home).toHaveAttribute('data-longitude', '51.3914');
            await expect(home.locator('.leaflet-marker-icon')).toBeVisible();
            await expect(home.locator('.leaflet-marker-draggable')).toHaveCount(0);
            await expect(home).not.toHaveClass(/leaflet-grab/);
            await capture('home-map', home);

            await writeFile(output + '/guard-' + width + '-' + theme + '.json', JSON.stringify(guard, null, 2));
            expect(errors).toEqual([]);
        });
    }
}
