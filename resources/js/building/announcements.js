import { toast } from '../ui';
import { mediaViewer } from '../media-viewer';

export function announcements() {
    document.querySelectorAll('[data-announcement-image]').forEach(button => {
        button.addEventListener('click', () => {
            mediaViewer().open({ id: 'announcement', url: button.dataset.announcementImage, alt: 'تصویر اعلان' }, button, 'announcement');
        });
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
