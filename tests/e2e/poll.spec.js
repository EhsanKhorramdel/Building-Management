import { expect, test } from '@playwright/test';
import { fields, formInPanel, login, openBuilding, submit, tab, toast } from './ui.js';

test.beforeEach(async ({ page }) => {
    await login(page, 'manager');
    await openBuilding(page);
});

async function publish(page, title, duration) {
    const form = await formInPanel(page, 'poll');
    await fields(form, { title, question: 'زمان جلسه؟', option1: 'شنبه', option2: 'یکشنبه', ...duration });
    await submit(form);
    await toast(page);
    const card = page.locator('.poll-window .building-box').filter({ hasText: title });
    await expect(card).toBeVisible();
    return card;
}

test('creates an hours-only poll, votes, changes the vote and draws results in both themes', async ({ page }) => {
    const card = await publish(page, 'نظرسنجی ساعتی آزمون', { endHours: '2' });
    const dialog = page.locator('.modal-vote');
    for (const [option, confirmation] of [[0, 'رای شما ثبت شد'], [1, 'رای شما بروزرسانی شد']]) {
        await card.locator('.open-Btn-vote').click();
        await dialog.getByRole('radio').nth(option).check();
        await submit(dialog);
        await expect(page.locator('.toast').filter({ hasText: confirmation })).toBeVisible();
    }

    for (const dark of [false, true]) {
        if (dark) await page.locator('.mode-switch').first().click();
        await expect(page.locator('.mode-switch').first()).toHaveAttribute('aria-pressed', String(dark));
        await card.locator('.open-Btn-vote-result').click();
        const chart = page.getByRole('img', { name: 'نمودار نتیجه رأی‌گیری' });
        await expect(chart).toBeVisible();
        await expect.poll(() => chart.evaluate(canvas => {
            const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
            return pixels.some((component, index) => index % 4 === 3 && component > 0);
        })).toBe(true);
        await page.keyboard.press('Escape');
        await expect(chart).toBeHidden();
    }
});

test('publishes a poll with minutes, hours and days filled in', async ({ page }) => {
    const card = await publish(page, 'نظرسنجی سه‌بخشی آزمون', { endMinutes: '15', endHours: '2', endDays: '1' });
    await expect(card.locator('.open-Btn-vote')).toBeVisible();
});

test('an expired poll hides voting and a stale ballot shows the expiry message', async ({ page }) => {
    await tab(page, 'poll');
    const expired = page.locator('.poll-window .building-box').filter({ hasText: 'نظرسنجی پایان‌یافته' });
    await expect(expired).toBeVisible();
    await expect(expired.locator('.open-Btn-vote')).toHaveCount(0);
    const option = await expired.locator('[data-option-id]').first().getAttribute('data-option-id');
    await page.locator('form[action$="/vote"]').evaluate((form, value) => {
        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = 'voteRadio';
        field.value = value;
        form.append(field);
        form.requestSubmit();
    }, option);
    await expect(page.locator('.toast[data-status=error]')).toContainText('مهلت شرکت در این رای گیری به پایان رسیده است.');
});
