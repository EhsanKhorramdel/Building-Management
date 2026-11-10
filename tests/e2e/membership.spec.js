import { expect, test } from '@playwright/test';
import { dashboardCard, fields, login, openBuilding, register, submit, tab, toast } from './ui.js';

async function requestBuilding(page, name, coordinates = {}) {
    await tab(page, 'register');
    const form = page.locator('.register-window form');
    await fields(form, { name, address: 'تهران، خیابان آزمون', floors: '2', units: '4', ...coordinates });
    if (coordinates.latitude) await expect(form.locator('.leaflet-marker-icon')).toBeVisible();
    await submit(form);
    await toast(page);
}

async function assignUnit(page, building, number) {
    const card = dashboardCard(page, building);
    await card.locator('summary').click();
    await card.locator('.open-add-unit-Btn').click();
    const dialog = page.locator('.add-unit-modal');
    await fields(dialog, { zipCode: '0012345678', unitNumber: number, floorNumber: '1' });
    await submit(dialog);
    await toast(page);
}

test('admin approves the mapped request and a resident joins the new complex', async ({ page, browser, baseURL }) => {
    const title = 'مجتمع راه‌اندازی آزمون';
    const adminContext = await browser.newContext({ baseURL });
    const residentContext = await browser.newContext({ baseURL });
    try {
        const admin = await adminContext.newPage();
        await register(page, 'new-manager', 'مدیر تازه');
        await requestBuilding(page, title, { latitude: '35.7065', longitude: '51.4171' });

        await login(admin, 'admin');
        const pending = admin.locator('#pending li').filter({ hasText: title });
        await pending.locator('summary').click();
        await expect(pending.locator('.leaflet-marker-icon')).toBeVisible();
        await submit(pending.locator('form[action*="/accept/"]'));
        await admin.locator('[data-admin-tab=accepted]').click();
        await expect(admin.locator('#accepted li').filter({ hasText: title })).toBeVisible();

        await page.reload();
        await expect(dashboardCard(page, title)).toBeVisible();
        await assignUnit(page, title, '1');
        const building = await openBuilding(page, title);
        await expect(page.locator('.home-window .leaflet-marker-icon')).toBeVisible();
        await expect(page.locator('.home-window [data-complex-map]')).toHaveAttribute('data-latitude', '35.7065');
        await expect(page.locator('.home-window .leaflet-marker-icon')).not.toHaveClass(/leaflet-interactive/);
        await tab(page, 'setting');
        const invitation = await page.locator('#join-link').inputValue();

        const resident = await residentContext.newPage();
        await login(resident, 'resident');
        const join = resident.locator('form[action$="/joinBuilding"]');
        await fields(join, { link: invitation });
        await submit(join);
        await toast(resident);
        await expect(dashboardCard(resident, title)).toBeVisible();
        await assignUnit(resident, title, '2');
        await openBuilding(resident, title);
        await expect(resident).toHaveURL(building);
        await expect(resident.getByText('تهران، خیابان آزمون')).toBeVisible();
        await expect(resident.locator('[data-tab=setting]')).toHaveCount(0);
    } finally {
        await Promise.all([adminContext.close(), residentContext.close()]);
    }
});

test('admin rejects the second request and no building appears for the applicant', async ({ page, browser, baseURL }) => {
    const title = 'مجتمع درخواست رد آزمون';
    const adminContext = await browser.newContext({ baseURL });
    try {
        const admin = await adminContext.newPage();
        await register(page, 'rejected-applicant', 'متقاضی دوم');
        await requestBuilding(page, title);
        await login(admin, 'admin');
        const row = admin.locator('#pending li').filter({ hasText: title });
        await submit(row.locator('form[action*="/reject/"]'));
        await admin.locator('[data-confirm-dialog] button[value=confirm]').click();
        await expect(row).toHaveCount(0);
        await admin.locator('[data-admin-tab=rejected]').click();
        await expect(admin.locator('#rejected li').filter({ hasText: title })).toBeVisible();
        await page.reload();
        await tab(page, 'home');
        await expect(dashboardCard(page, title)).toHaveCount(0);
    } finally {
        await adminContext.close();
    }
});
