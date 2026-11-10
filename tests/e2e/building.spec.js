import { expect, test } from '@playwright/test';
import { fields, formInPanel, login, openBuilding, submit, tab, toast, uploadImage } from './ui.js';

test.beforeEach(async ({ page }) => {
    await login(page, 'manager');
    await openBuilding(page);
});

test('publishes an announcement and opens its uploaded image preview', async ({ page }) => {
    const text = 'اعلان تصویری آزمون';
    const form = await formInPanel(page, 'notification');
    await fields(form, { notifText: text });
    await form.locator('[name=notifPicture]').setInputFiles(uploadImage);
    await submit(form);
    await toast(page);
    const announcement = page.locator('.notification-window .building-box').filter({ hasText: text });
    await announcement.getByRole('button', { name: 'مشاهده تصویر' }).click();
    const preview = page.locator('.modal-announcement img');
    await expect(preview).toBeVisible();
    await expect.poll(() => preview.evaluate(image => image.naturalWidth)).toBe(192);
});

test('creates a charge, records a manual payment and prints the report', async ({ page }) => {
    const year = new Intl.DateTimeFormat('en-u-ca-persian-nu-latn', { year: 'numeric' })
        .formatToParts(new Date()).find(part => part.type === 'year').value;
    const charge = await formInPanel(page, 'charge');
    await fields(charge, {
        chargeAmount: '12345', dayStart: '1', monthStart: '1', yearStart: String(Number(year) - 1),
        dayEnd: '29', monthEnd: '12', yearEnd: String(Number(year) + 1),
    });
    await submit(charge);
    await toast(page);

    const card = page.locator('.charge-window .building-box').filter({ hasText: '12345' });
    await card.locator('.charge-pay-open-Btn').click();
    const payment = page.locator('.charge-pay-modal');
    await fields(payment, { unitNumber: '1' });
    await submit(payment);
    await toast(page);

    await tab(page, 'paymentInfo');
    const row = page.locator('[data-payments-table] tbody tr').filter({ hasText: '12345' });
    await expect(row).toContainText('مدیر سرو');
    await page.evaluate(() => { window.print = () => { document.body.dataset.printInvoked = 'true'; }; });
    await page.getByRole('button', { name: 'چاپ گزارش' }).click();
    await expect(page.locator('body')).toHaveAttribute('data-print-invoked', 'true');
    await page.emulateMedia({ media: 'print' });
    await expect(page.locator('.app-header')).toBeHidden();
    await expect(row).toBeVisible();
});
