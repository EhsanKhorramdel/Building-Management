import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';

const output = 'storage/app/test-screenshots/phase-8c';
test.skip(process.env.VISUAL_FINDINGS !== '1', 'Requires an isolated seeded server.');

async function openBuilding(page) {
    await mkdir(output, { recursive: true });
    await page.setViewportSize({ width: 390, height: 820 });
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await page.locator('[name=email]').fill(process.env.DEMO_MANAGER_EMAIL);
    await page.locator('[name=password]').fill(process.env.DEMO_PASSWORD);
    await page.locator('form button[type=submit]').first().click();
    await page.waitForURL('**/dashboard', { waitUntil: 'domcontentloaded' });
    const building = await page.locator('a[href*="/dashboard/building/"]').first().getAttribute('href');
    await page.goto(building, { waitUntil: 'domcontentloaded' });
}

async function openTab(page, name) {
    await page.locator('[data-open-drawer]').click();
    await page.locator('[data-tab=' + name + ']').click();
}

test('publishes an hours-only browser poll and votes on it', async ({ page }) => {
    await openBuilding(page);
    await openTab(page, 'poll');
    await page.locator('.register-btn').click();
    const editor = page.locator('.register-section[data-panel=poll] form');
    const title = 'نظرسنجی بررسی ' + Date.now();
    for (const [field, value] of Object.entries({ title, question: 'زمان جلسه؟', endHours: '2', option1: 'شنبه', option2: 'یکشنبه' })) {
        await editor.locator('[name=' + field + ']').fill(value);
    }
    await Promise.all([
        page.waitForEvent('domcontentloaded'),
        editor.locator('button[type=submit]').click({ noWaitAfter: true }),
    ]);
    const poll = page.locator('.poll-window .building-box').filter({ hasText: title });
    await expect(poll).toBeVisible();
    await page.screenshot({ path: output + '/flow-poll-listed.png' });

    await poll.locator('.open-Btn-vote').click();
    await page.locator('.modal-vote [name=voteRadio]').first().check();
    const [response] = await Promise.all([
        page.waitForResponse(response => response.url().endsWith('/vote') && response.request().method() === 'POST'),
        page.locator('.modal-vote button[type=submit]').click(),
    ]);
    expect(response.status()).toBe(302);
    await expect(page.locator('.toast[data-status=success]').first()).toBeVisible();
    await page.screenshot({ path: output + '/flow-poll-voted.png' });
});

test('opens the latest page and scrolls down after loading older chat', async ({ page }) => {
    await openBuilding(page);
    const csrf = await page.locator('meta[name=csrf-token]').getAttribute('content');
    const complexId = new URL(page.url()).pathname.split('/').at(-1);
    const prefix = 'پیام بررسی ' + Date.now() + ' ';
    for (let number = 1; number <= 24; number++) {
        const response = await page.request.post('/dashboard/building/group/send', {
            headers: { 'X-CSRF-TOKEN': csrf },
            form: { complexId, messageText: prefix + number },
        });
        expect(response.ok()).toBe(true);
    }

    await openTab(page, 'chat');
    const older = page.locator('[data-older]');
    const early = page.locator('.chat-message').filter({ has: page.getByText(prefix + '6', { exact: true }) });
    await older.click();
    await expect(early).toBeVisible();
    await page.screenshot({ path: output + '/flow-chat-older.png' });

    const [response] = await Promise.all([
        page.waitForResponse(response => response.url().endsWith('/group/0')),
        page.getByRole('button', { name: 'رفتن به آخرین پیام' }).click(),
    ]);
    expect(response.ok()).toBe(true);
    await expect(early).toHaveCount(0);
    await expect(page.locator('.chat-message').last()).toContainText(prefix + '24');
    const scroll = page.locator('.chatContainer');
    await expect.poll(() => scroll.evaluate(element => element.scrollHeight - element.clientHeight - element.scrollTop)).toBeLessThanOrEqual(1);
    await expect(older).toBeVisible();
    await expect(page.locator('[data-newer]')).toBeVisible();
    await page.screenshot({ path: output + '/flow-chat-latest.png' });
});

test('shows the report scroll cue on a phone and omits it in print', async ({ page }) => {
    await openBuilding(page);
    await openTab(page, 'paymentInfo');
    const report = page.locator('[data-payments-table]');
    const cue = page.locator('[data-report-scroll-cue]');
    const frame = page.locator('[data-report-scroll-frame]');
    await expect(report.locator('tbody tr').first()).toBeVisible();
    await expect(cue).toBeVisible();
    await expect(frame).toHaveClass(/report-has-more/);
    await report.evaluate(element => { element.scrollLeft = -element.scrollWidth; });
    await expect(frame).not.toHaveClass(/report-has-more/);

    await page.emulateMedia({ media: 'print' });
    await expect(cue).toBeHidden();
    await expect(report.locator('tbody tr').first()).toBeVisible();
    await expect(page.locator('.app-header')).toBeHidden();
});
