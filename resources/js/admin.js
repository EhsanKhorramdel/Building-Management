import './ui';

const buttons = [...document.querySelectorAll('[data-admin-tab]')];
for (const button of buttons) {
    button.addEventListener('click', () => {
        buttons.forEach(item => {
            const selected = item === button;
            document.getElementById(item.dataset.adminTab).hidden = !selected;
            item.setAttribute('aria-pressed', String(selected));
        });
    });
}
