import { openDialog, closeDialog } from '../ui';

export function navigation(initial = 'home') {
    const drawer = document.querySelector('[data-drawer]');
    const opener = document.querySelector('[data-open-drawer]');
    const overlay = document.querySelector('[data-drawer-overlay]');
    const links = [...drawer.querySelectorAll('[data-tab]')];
    const register = document.querySelector('.register-btn');
    const background = [opener.closest('.app-header'), ...drawer.parentElement.children]
        .filter(element => element && element !== drawer && element !== overlay);
    let madeInert = [];

    function closeDrawer(restore = true) {
        drawer.classList.remove('drawer-open');
        overlay.hidden = true;
        opener.setAttribute('aria-expanded', 'false');
        madeInert.forEach(element => { element.inert = false; });
        madeInert = [];
        if (restore) opener.focus();
    }

    function select(tab, persist = false) {
        const link = links.find(item => item.dataset.tab === tab);
        if (!link) return;
        document.querySelectorAll('.buildings-section[data-panel]').forEach(panel => {
            panel.hidden = panel.dataset.panel !== tab;
        });
        links.forEach(item => {
            const current = item === link;
            item.classList.toggle('active', current);
            if (current) item.setAttribute('aria-current', 'page');
            else item.removeAttribute('aria-current');
        });
        const panel = document.querySelector('.register-section[data-panel="' + tab + '"]');
        if (register) {
            register.hidden = !panel;
            register.onclick = () => openDialog(panel, register);
        }
        const wasOpen = drawer.classList.contains('drawer-open');
        closeDrawer(wasOpen);
        document.dispatchEvent(new CustomEvent('panelchange', { detail: tab }));
        if (persist) document.dispatchEvent(new CustomEvent('building-panel-selected', { detail: tab }));
    }

    opener.addEventListener('click', () => {
        madeInert = background.filter(element => !element.inert);
        madeInert.forEach(element => { element.inert = true; });
        drawer.classList.add('drawer-open');
        overlay.hidden = false;
        opener.setAttribute('aria-expanded', 'true');
        drawer.querySelector('[data-close-drawer]').focus();
    });
    overlay.addEventListener('click', () => closeDrawer());
    drawer.querySelector('[data-close-drawer]').addEventListener('click', () => closeDrawer());
    links.forEach(link => link.addEventListener('click', () => select(link.dataset.tab, true)));
    window.addEventListener('resize', () => {
        if (drawer.classList.contains('drawer-open') && !opener.getClientRects().length) closeDrawer(false);
    });
    document.addEventListener('keydown', event => {
        if (!drawer.classList.contains('drawer-open')) return;
        if (event.key === 'Escape') closeDrawer();
        if (event.key !== 'Tab') return;
        const controls = [...drawer.querySelectorAll('button:not(:disabled), a[href]')]
            .filter(element => element.getClientRects().length);
        const boundary = event.shiftKey ? controls[0] : controls.at(-1);
        if (document.activeElement !== boundary && drawer.contains(document.activeElement)) return;
        event.preventDefault();
        (event.shiftKey ? controls.at(-1) : controls[0])?.focus();
    });
    document.querySelectorAll('.register-close').forEach(button => button.addEventListener('click', closeDialog));
    select(initial);
    const reopened = document.querySelector('[data-reopen]');
    if (reopened) openDialog(reopened, register || links.find(link => link.dataset.tab === initial));
}
