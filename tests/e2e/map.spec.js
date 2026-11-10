import { expect, test } from '@playwright/test';
import { fields, login, openBuilding, submit, tab, toast } from './ui.js';

test('manager saves a map point, resident sees the read-only map, and clearing removes it', async ({ page, browser, baseURL }) => {
    const residentContext = await browser.newContext({ baseURL });
    try {
        await login(page, 'manager');
        await openBuilding(page);
        await tab(page, 'setting');
        const form = page.locator('.setting-window form[action$="/location"]');
        await fields(form, { latitude: '35.7065', longitude: '51.4171' });
        await expect(form.locator('.leaflet-marker-icon')).toBeVisible();
        await submit(form);
        await toast(page);
        await tab(page, 'home');
        const map = page.locator('.home-window [data-complex-map]');
        await expect(map.locator('.leaflet-marker-icon')).toBeVisible();
        const directions = page.getByRole('link', { name: 'مسیریابی روی نقشه' });
        await expect(directions).toHaveAttribute('href', /35\.7065%2C51\.4171/);

        const resident = await residentContext.newPage();
        await login(resident, 'resident');
        await openBuilding(resident);
        await expect(resident.locator('.home-window .leaflet-marker-icon')).toBeVisible();
        await expect(resident.locator('[data-tab=setting]')).toHaveCount(0);
        await expect(resident.locator('input[name=latitude]')).toHaveCount(0);

        await tab(page, 'setting');
        await form.locator('[data-map-clear]').click();
        await expect(form.locator('[name=latitude]')).toHaveValue('');
        await expect(form.locator('.leaflet-marker-icon')).toHaveCount(0);
        await submit(form);
        await toast(page);
        await tab(page, 'home');
        await expect(map).toHaveCount(0);
        await expect(directions).toHaveCount(0);
        await expect(page.locator('[data-map-settings]')).toBeVisible();
    } finally {
        await residentContext.close();
    }
});
