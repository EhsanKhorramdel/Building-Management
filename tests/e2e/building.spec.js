import { expect, test } from '@playwright/test';
import { fields, formInPanel, login, openBuilding, submit, tab, toast, uploadImage } from './ui.js';

test.beforeEach(async ({ page }) => {
    await login(page, 'manager');
    await openBuilding(page);
});

async function checkAttachmentAccess(browser, baseURL, url) {
    const memberContext = await browser.newContext({ baseURL });
    const outsiderContext = await browser.newContext({ baseURL });
    try {
        const member = await memberContext.newPage();
        await login(member, 'resident');
        const image = await member.goto(url);
        expect(image.status()).toBe(200);
        expect(image.headers()['content-type']).toBe('image/png');
        await expect.poll(() => member.locator('img').evaluate(element => element.naturalWidth)).toBe(192);

        const outsider = await outsiderContext.newPage();
        const guest = await outsider.goto(url);
        await expect(outsider).toHaveURL(/\/login$/);
        expect(guest.headers()['content-type']).not.toMatch(/^image\//);

        await outsiderContext.clearCookies();
        await login(outsider, 'other-resident');
        const forbidden = await outsider.goto(url);
        expect(forbidden.status()).toBe(403);
        expect(forbidden.headers()['content-type']).not.toMatch(/^image\//);
    } finally {
        await Promise.all([memberContext.close(), outsiderContext.close()]);
    }
}

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
    await expect(preview).toHaveAttribute('src', /\/announcements\/\d+\/attachment$/);
    await expect.poll(() => preview.evaluate(image => image.naturalWidth)).toBe(192);
});

test('an announcement image opens for a resident and its URL is denied to outsiders', async ({ page, browser, baseURL }) => {
    await tab(page, 'notification');
    const announcement = page.locator('.notification-window .building-box').filter({ hasText: 'جلسه هفتگی مجتمع سرو' });
    const button = announcement.getByRole('button', { name: 'مشاهده تصویر' });
    await expect(button).toBeVisible();
    await expect(button).toHaveAttribute('data-announcement-image', /\/announcements\/\d+\/attachment$/);
    expect(await page.content()).not.toMatch(/\/storage\/(?:notificationFiles|incidentalCostFiles)\//);
    await checkAttachmentAccess(browser, baseURL, await button.getAttribute('data-announcement-image'));
});

test('an uploaded cost invoice previews and its URL is denied to outsiders', async ({ page, browser, baseURL }) => {
    const title = 'فاکتور هزینه آزمون';
    const form = await formInPanel(page, 'cost');
    await fields(form, { title, total_amount: '120000', share_amount: '40000' });
    await form.locator('[name=cost_invoice]').setInputFiles(uploadImage);
    await submit(form);
    await toast(page);

    const cost = page.locator('.cost-window .building-box').filter({ hasText: title });
    await cost.getByRole('button', { name: 'مشاهده فاکتور' }).click();
    const preview = page.locator('.cost-modal img');
    await expect(preview).toBeVisible();
    await expect(preview).toHaveAttribute('src', /\/incidental-costs\/\d+\/attachment$/);
    await expect.poll(() => preview.evaluate(image => image.naturalWidth)).toBe(192);
    expect(await page.content()).not.toMatch(/\/storage\/(?:notificationFiles|incidentalCostFiles)\//);
    await checkAttachmentAccess(browser, baseURL, await preview.getAttribute('src'));
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
