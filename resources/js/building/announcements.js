import { openDialog, closeDialog, toast } from '../ui';

export function announcements() {
    document.querySelectorAll('[data-announcement-image]').forEach(button => {
        button.addEventListener('click', () => {
            const dialog = document.querySelector('.modal-announcement');
            const image = dialog?.querySelector('img');
            if (!image) return;
            image.src = button.dataset.announcementImage;
            openDialog(dialog, button);
        });
    });
    document.querySelectorAll('.close-Btn-announcement').forEach(button => button.addEventListener('click', closeDialog));
    document.querySelector('[data-copy-join]')?.addEventListener('click', async event => {
        try {
            await navigator.clipboard.writeText(event.currentTarget.dataset.copyJoin);
            toast('لینک کپی شد.');
        } catch {
            toast('کپی لینک انجام نشد.', 'error');
        }
    });
}
