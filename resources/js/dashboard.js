import { openDialog, closeDialog } from './ui';
import { navigation } from './building/navigation';

navigation(document.body.dataset.initialPanel);
for (const card of document.querySelectorAll('[data-complex-id]')) {
    const details = card.querySelector('details');
    for (const [buttonSelector, dialogSelector] of [['.open-add-unit-Btn', '.add-unit-modal'], ['.open-Btn', '.unit-edit-modal']]) {
        const button = card.querySelector(buttonSelector);
        const dialog = document.querySelector(dialogSelector);
        button.addEventListener('click', () => {
            dialog.querySelector('[name=complexId]').value = card.dataset.complexId;
            details.open = false;
            openDialog(dialog, details.querySelector('summary'));
        });
    }
}
document.querySelectorAll('.close-add-unit-Btn, .close-Btn').forEach(button => button.addEventListener('click', closeDialog));
