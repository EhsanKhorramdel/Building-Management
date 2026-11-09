import { toast } from '../ui';

export const config = JSON.parse(document.querySelector('#building-config')?.textContent || '{}');
export const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

/** @returns {Promise<unknown>} Parsed JSON, true for a successful bodyless request, or null after displaying an error. */
export async function request(url, { method = 'GET', data, json = true } = {}) {
    try {
        const headers = { Accept: 'application/json' };
        if (method !== 'GET') headers['X-CSRF-TOKEN'] = csrf();
        const body = data instanceof FormData ? data : data && new URLSearchParams(data);
        const response = await fetch(url, { method, headers, body });
        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            const validation = Object.values(payload?.errors || {}).flat()[0];
            throw new Error(validation || 'ارتباط با سامانه برقرار نشد. دوباره تلاش کنید.');
        }
        return json ? await response.json() : true;
    } catch (error) {
        toast(error instanceof TypeError || error instanceof SyntaxError ? 'ارتباط با سامانه برقرار نشد. دوباره تلاش کنید.' : error.message, 'error');
        return null;
    }
}
