import { test, expect } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';

const output = 'storage/app/test-screenshots/phase-8';

test.skip(process.env.VISUAL_PAYMENTS !== '1', 'Requires a fresh demo server with mock payments enabled.');

for (const width of [390, 1440]) {
    for (const theme of ['light', 'dark']) {
        test('mock checkout and online payments at ' + width + 'px in ' + theme, async ({ page, baseURL }) => {
            test.setTimeout(60_000);
            await mkdir(output, { recursive: true });
            await page.setViewportSize({ width, height: width < 768 ? 820 : 900 });
            await page.context().addCookies([{
                name: 'userSettings',
                value: encodeURIComponent(JSON.stringify({ darkMode: theme === 'dark', viewMode: 'gridView' })),
                url: baseURL,
            }]);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            page.on('console', message => {
                if (message.type() === 'error') errors.push(message.location().url + ' ' + message.text());
            });

            const guards = [];
            async function capture(name) {
                await page.evaluate(() => document.fonts.ready);
                await page.screenshot({ path: output + '/' + name + '-' + width + '-' + theme + '.png', animations: 'disabled' });
                const metrics = await page.evaluate(() => ({
                    width: innerWidth,
                    scrollWidth: document.scrollingElement.scrollWidth,
                    dark: document.documentElement.classList.contains('dark'),
                }));
                guards.push({ name, ...metrics, errors: [...errors] });
                expect(metrics.scrollWidth).toBeLessThanOrEqual(width);
                expect(metrics.dark).toBe(theme === 'dark');
            }

            await page.goto('/login');
            await page.locator('[name=email]').fill(process.env.DEMO_MANAGER_EMAIL);
            await page.locator('[name=password]').fill(process.env.DEMO_PASSWORD);
            await page.locator('form button[type=submit]').first().click();
            await page.waitForURL('**/dashboard');
            const building = await page.locator('a[href*="/dashboard/building/"]').first().getAttribute('href');
            await page.goto(building, { waitUntil: 'domcontentloaded' });
            if (width < 768) await page.locator('[data-open-drawer]').click();
            await page.locator('[data-tab=cost]').click();
            await page.locator('.cost-pay-open-Btn').first().click();
            const dialog = page.locator('.cost-pay-modal');
            await dialog.locator('[name=unitNumber]').fill('1');
            await Promise.all([
                page.waitForURL('**/payments/mock/*'),
                dialog.locator('button[type=submit]').click(),
            ]);
            await expect(page.locator('h1')).toHaveText('پرداخت آزمایشی');
            await expect(page.locator('[data-mock-outcome=pay]')).toBeVisible();
            await expect(page.locator('[data-mock-outcome=cancel]')).toBeVisible();
            await capture('mock-checkout');

            const pay = width === 1440 && theme === 'dark';
            await Promise.all([
                page.waitForURL(building, { waitUntil: 'domcontentloaded' }),
                page.locator('[data-mock-outcome=' + (pay ? 'pay' : 'cancel') + ']').click(),
            ]);
            await expect(page.locator('.paymentInfo-window')).toBeVisible();
            await expect(page.locator('[data-payments-state][data-loading]')).toHaveCount(0);
            const history = page.locator('[data-online-payments]');
            await expect(history.getByRole('button', { name: 'پرداخت آنلاین شارژ' })).toBeVisible();
            await expect(history.getByRole('button', { name: 'پرداخت آنلاین هزینه جانبی' })).toBeVisible();
            await expect(history.locator('tbody tr').first()).toContainText(pay ? 'پرداخت‌شده' : 'لغوشده');
            if (pay) await expect(history.locator('tbody tr').first()).toContainText('mock-');
            await capture('online-payments');

            await writeFile(output + '/guard-' + width + '-' + theme + '.json', JSON.stringify(guards, null, 2));
            expect(errors).toEqual([]);
        });
    }
}
