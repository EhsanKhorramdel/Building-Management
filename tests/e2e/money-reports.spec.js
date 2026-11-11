import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdir, readFile } from 'node:fs/promises';
import { environments, php } from './environment.js';
import { seed } from './global-setup.js';
import { login, openBuilding, submit, tab, theme } from './ui.js';

test.beforeEach(() => {
    const { app, env } = environments.online;
    execFileSync(php, ['artisan', 'migrate:fresh', '--seed', '--seeder=E2EMoneySeeder', '--force'], {
        cwd: app, env: { ...process.env, ...env }, stdio: 'pipe',
    });
});
test.afterAll(() => seed('online'));

async function capture(page, name) {
    const directory = 'storage/app/test-screenshots/phase-11f';
    await mkdir(directory, { recursive: true });
    await page.screenshot({ path: directory + '/' + name + '.png', animations: 'disabled' });
}

for (const width of [390, 1440]) for (const dark of [false, true]) {
    const state = width + '-' + (dark ? 'dark' : 'light');
    test('filter export receipt reconciliation and history at ' + state, async ({ browser }) => {
        test.setTimeout(180_000);
        const baseURL = environments.online.url;
        const options = { baseURL, viewport: { width, height: 844 }, hasTouch: width === 390 };
        const staffContext = await browser.newContext(options);
        const payerContext = await browser.newContext(options);
        const staff = await staffContext.newPage();
        const payer = await payerContext.newPage();
        const errors = [];
        for (const page of [staff, payer]) page.on('pageerror', error => errors.push(error.message));
        try {
            await theme(staffContext, baseURL, dark);
            await theme(payerContext, baseURL, dark);
            await login(staff, 'manager');
            const building = await openBuilding(staff);
            await tab(staff, 'paymentInfo');
            const rows = staff.locator('[data-payments-table] tbody tr');
            await expect(rows).toHaveCount(2);
            await expect(staff.locator('[data-online-payments] .empty-state')).toBeVisible();
            await staff.locator('.payment-filters summary').click();
            const filters = staff.locator('[data-report-filters]');
            await filters.locator('[name=unit_number]').fill('۲');
            await filters.locator('[name=type]').selectOption('charge');
            await submit(filters);
            await expect(rows).toHaveCount(1);
            await expect(staff.locator('[data-report-scope]')).toContainText('واحد ۲');
            await capture(staff, 'filtered-' + state);
            const displayed = (await rows.first().locator('td').allTextContents()).slice(0, 5);
            await filters.locator('[name=type]').selectOption('cost');
            const [download] = await Promise.all([staff.waitForEvent('download'), staff.locator('[data-report-export]').click()]);
            expect(download.suggestedFilename()).toBe('payments.csv');
            const csv = await readFile(await download.path());
            expect([...csv.subarray(0, 3)]).toEqual([239, 187, 191]);
            const lines = csv.subarray(3).toString().trimEnd().split(/\r?\n/);
            expect(lines).toHaveLength(2);
            expect(lines[1].split(',').map(cell => cell.replace(/^"|"$/g, ''))).toEqual(displayed);
            await staff.emulateMedia({ media: 'print' });
            await expect(staff.locator('[data-report-scope]')).toContainText('مجتمع سرو');
            await expect(staff.locator('[data-report-scope]')).toContainText('شارژ');
            await expect(rows).toHaveCount(1);
            await expect(filters).toBeHidden();
            await staff.emulateMedia({ media: 'screen' });
            await filters.getByRole('button', { name: 'پاک کردن فیلترها' }).click();
            await expect(rows).toHaveCount(2);
            await expect(staff.locator('[data-report-scope]')).toContainText('همه واحدها');
            await rows.filter({ hasText: 'شارژ' }).getByRole('link', { name: 'رسید', exact: true }).click();
            await expect(staff.getByRole('heading', { name: 'رسید پرداخت' })).toBeVisible();
            await expect(staff.locator('[data-receipt-number]')).toHaveText(/^CHARGE-\d+$/);
            await expect(staff.locator('.payment-receipt')).toContainText('دستی');
            await expect(staff.locator('.payment-receipt')).toContainText('ساکن سرو');
            await staff.evaluate(() => { window.print = () => { document.body.dataset.receiptPrinted = 'true'; }; });
            await staff.locator('[data-print-receipt]').click();
            await expect(staff.locator('body')).toHaveAttribute('data-receipt-printed', 'true');
            await capture(staff, 'receipt-' + state);

            await staff.goto(building);
            await tab(staff, 'paymentInfo');
            await staff.getByRole('link', { name: 'بررسی پرداخت‌ها', exact: true }).click();
            await expect(staff.getByRole('heading', { name: 'بررسی پرداخت‌های آنلاین' })).toBeVisible();
            const attempt = staff.locator('[data-reconciliation-attempt]').first();
            await expect(attempt).toContainText('••••6198');
            await expect(attempt).toContainText('عدم تطابق پاسخ پرداخت');
            await expect(attempt.locator('.payment-timeline li')).toHaveCount(2);
            expect(await staff.content()).not.toMatch(/fixture-(?:private|finance)|مجتمع کاج/);
            await capture(staff, 'reconciliation-' + state);

            await login(payer, 'resident');
            await openBuilding(payer);
            await tab(payer, 'paymentInfo');
            const history = payer.locator('[data-online-payments] tbody tr');
            await expect(history).toHaveCount(15);
            await payer.locator('[data-more-payments]').click();
            await expect(history).toHaveCount(17);
            await expect(payer.locator('[data-more-payments]')).toBeHidden();
            const ids = await history.evaluateAll(rows => rows.map(row => Number(row.dataset.transactionId)));
            expect(ids).toEqual([...new Set(ids)].sort((a, b) => b - a));
            await history.last().getByRole('link', { name: 'رسید', exact: true }).click();
            await expect(payer.locator('[data-receipt-number]')).toHaveText(/^COST-\d+$/);
            await expect(payer.locator('.payment-receipt')).toContainText('older-receipt-reference');
            await expect(payer.locator('.payment-receipt')).toContainText('آنلاین');
            for (const page of [staff, payer]) expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
            expect(errors).toEqual([]);
        } finally {
            await Promise.all([staffContext.close(), payerContext.close()]);
        }
    });
}
