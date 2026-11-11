import { toast, openDialog } from '../ui';
import { mediaViewer } from '../media-viewer';
import { config, request } from './api';

export function announcements() {
    const panel = document.querySelector('.notification-window');
    const list = panel.querySelector('[data-announcement-list]');
    const marked = new Set();
    const observer = new IntersectionObserver(entries => {
        for (const entry of entries) {
            const card = entry.target;
            const id = card.dataset.announcementId;
            if (!entry.isIntersecting || panel.hidden || card.dataset.read === '1' || marked.has(id) || card.dataset.archived === '1') continue;
            marked.add(id);
            void request('/dashboard/announcements/' + id + '/read', { method: 'POST' }).then(result => {
                if (!result) { marked.delete(id); return; }
                card.dataset.read = '1';
                card.querySelector('[data-announcement-unread]')?.remove();
                const count = card.querySelector('[data-announcement-count]');
                if (count && result.count !== undefined) count.textContent = result.count.toLocaleString('fa-IR');
                observer.unobserve(card);
            });
        }
    });
    const observe = () => list.querySelectorAll('[data-announcement-id]').forEach(card => observer.observe(card));
    observe();
    const search = panel.querySelector('[data-announcement-search]');
    search?.addEventListener('submit', async event => {
        event.preventDefault();
        const button = search.querySelector('button');
        button.disabled = true;
        try {
            const result = await request('/dashboard/building/' + config.complexId + '/announcements/search?' + new URLSearchParams(new FormData(search)));
            if (!result) return;
            observer.disconnect();
            list.replaceChildren(document.createRange().createContextualFragment(result.html));
            observe();
        } finally { button.disabled = false; }
    });
    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-announcement-image]');
        if (button) {
            mediaViewer().open({ id: 'announcement', url: button.dataset.announcementImage, alt: 'تصویر اعلان' }, button, 'announcement');
        }
        const readers = event.target.closest('[data-announcement-readers]');
        if (readers) {
            const result = await request(readers.dataset.announcementReaders);
            if (!result) return;
            const dialog = document.querySelector('[data-announcement-readers-dialog]');
            const rows = result.readers.map(reader => {
                const row = document.createElement('p');
                row.textContent = reader.name + ' · ' + new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(reader.read_at));
                return row;
            });
            if (!rows.length) { const empty = document.createElement('p'); empty.textContent = 'هنوز کسی این اعلان را نخوانده است.'; rows.push(empty); }
            dialog.querySelector('[data-announcement-readers-list]').replaceChildren(...rows);
            openDialog(dialog, readers);
        }
    });
    document.querySelector('[data-copy-join]')?.addEventListener('click', async event => {
        try {
            await navigator.clipboard.writeText(event.currentTarget.dataset.copyJoin);
            toast('لینک کپی شد.');
        } catch {
            toast('کپی لینک انجام نشد.', 'error');
        }
    });
}
