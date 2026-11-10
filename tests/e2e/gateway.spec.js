import { expect, test } from '@playwright/test';
import { environments } from './environment.js';
import { fields, login, openBuilding, submit, tab } from './ui.js';

for (const [name, account, unit, button, status, message] of [
    ['pays and shows its reference', 'manager', '1', 'pay', 'پرداخت‌شده', 'پرداخت با موفقیت انجام شد.'],
    ['cancels without a payment', 'neighbor', '3', 'cancel', 'لغوشده', 'پرداخت لغو شد.'],
    ['rejects a tampered amount without a payment', 'resident', '2', 'pay', 'ناموفق', 'پرداخت تأیید نشد.'],
]) {
    test(`mock checkout ${name}`, async ({ page }) => {
        await login(page, account, environments.online.url);
        await openBuilding(page);
        await tab(page, 'cost');
        const cost = page.locator('.cost-window .building-box').filter({ hasText: 'سرویس آسانسور' });
        await cost.locator('.cost-pay-open-Btn').click();
        const payment = page.locator('.cost-pay-modal');
        await fields(payment, { unitNumber: unit });
        await submit(payment);
        await expect(page.getByRole('heading', { name: 'پرداخت آزمایشی' })).toBeVisible();

        const checkout = page.locator(`[data-mock-outcome="${button}"]`);
        if (status === 'ناموفق') {
            await checkout.locator('..').locator('[name=amount]').evaluate(input => { input.value = '1.00'; });
        }
        await checkout.click();
        await expect(page.locator('.toast').filter({ hasText: message })).toBeVisible();
        const history = page.locator('[data-online-payments] tbody tr').filter({ hasText: 'سرویس آسانسور' });
        await expect(history).toBeVisible();
        await expect(history).toContainText(status);
        const report = page.locator('[data-payments-table] tbody');
        await expect(page.locator('[data-payments-table]')).toBeVisible();
        const costRow = report.locator('tr').filter({ has: page.getByRole('cell', { name: unit, exact: true }) }).filter({ hasText: '75000' });
        if (status === 'پرداخت‌شده') {
            await expect(history).toContainText(/mock-\d+/);
            await expect(costRow).toBeVisible();
        } else {
            await expect(history).not.toContainText(/mock-\d+/);
            await expect(costRow).toHaveCount(0);
        }
    });
}
