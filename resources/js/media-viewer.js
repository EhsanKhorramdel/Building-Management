import { closeDialog, openDialog } from './ui';
import { config, request } from './building/api';

let viewer;

export function mediaViewer() {
    if (viewer) return viewer;
    const dialog = document.createElement('dialog');
    dialog.className = 'media-viewer';
    dialog.setAttribute('aria-label', 'نمایش تصویر');
    dialog.innerHTML = `<div class="media-tools">
        <button type="button" data-media-close aria-label="بستن تصویر">بستن</button>
        <button type="button" data-media-previous aria-label="تصویر قبلی">قبلی</button>
        <button type="button" data-media-next aria-label="تصویر بعدی">بعدی</button>
        <button type="button" data-media-in aria-label="بزرگ‌نمایی">+</button>
        <button type="button" data-media-out aria-label="کوچک‌نمایی">−</button>
        <a data-media-download download>دریافت تصویر</a>
    </div><p data-media-state role="status"></p>
    <div class="media-stage"><img alt="" draggable="false"></div>`;
    document.body.append(dialog);
    const photo = dialog.querySelector('img');
    const stage = dialog.querySelector('.media-stage');
    const state = dialog.querySelector('[data-media-state]');
    const previous = dialog.querySelector('[data-media-previous]');
    const next = dialog.querySelector('[data-media-next]');
    const download = dialog.querySelector('[data-media-download]');
    const pointers = new Map();
    const deleted = new Set();
    let items, current, gallery, exhausted, busy, generation = 0;
    let scale = 1, x = 0, y = 0, start, pinch;

    function transform() {
        photo.style.transform = `translate(${x}px, ${y}px) scale(${scale})`;
        stage.dataset.zoomed = String(scale > 1);
        dialog.querySelector('[data-media-out]').disabled = scale <= 1;
        dialog.querySelector('[data-media-in]').disabled = scale >= 5;
    }
    function zoom(value) {
        scale = Math.max(1, Math.min(5, value));
        if (scale === 1) x = y = 0;
        transform();
    }
    function controls() {
        const ids = [...items.keys()].sort((a, b) => a - b);
        previous.disabled = busy || !gallery || (current === ids[0] && exhausted.before);
        next.disabled = busy || !gallery || (current === ids.at(-1) && exhausted.after);
    }
    function show(item) {
        current = item.id;
        scale = 1; x = y = 0;
        transform();
        state.textContent = 'در حال دریافت تصویر…';
        photo.hidden = true;
        photo.alt = item.alt;
        photo.onload = () => { photo.hidden = false; state.textContent = item.alt; };
        photo.onerror = () => { photo.hidden = true; state.textContent = 'تصویر در دسترس نیست. دوباره باز کنید.'; };
        photo.src = item.url;
        download.href = item.url;
        download.download = 'image-' + item.id;
        controls();
    }
    async function move(direction) {
        if (busy || !gallery) return;
        const focus = dialog.contains(document.activeElement) ? document.activeElement : null;
        let ids = [...items.keys()].sort((a, b) => a - b);
        let target = ids[ids.indexOf(current) + (direction === 'before' ? -1 : 1)];
        if (target === undefined && !exhausted[direction]) {
            busy = true;
            controls();
            const version = generation;
            state.textContent = 'در حال دریافت تصویر…';
            const page = await request('/dashboard/building/' + config.complexId + '/chat-media?'
                + new URLSearchParams({ direction, cursor: current }));
            if (version !== generation) return;
            busy = false;
            if (!page) {
                state.textContent = 'تصاویر دریافت نشدند. دوباره تلاش کنید.';
                controls();
                if (focus && !focus.disabled) focus.focus();
                return;
            }
            for (const item of page.images) if (!deleted.has(item.id)) items.set(item.id, item);
            exhausted[direction] = page.next_cursor === null;
            ids = [...items.keys()].sort((a, b) => a - b);
            target = ids[ids.indexOf(current) + (direction === 'before' ? -1 : 1)];
        }
        if (target !== undefined) show(items.get(target));
        else { state.textContent = 'تصویر دیگری وجود ندارد.'; controls(); }
        if (focus && !focus.disabled) focus.focus();
    }
    dialog.querySelector('[data-media-close]').addEventListener('click', closeDialog);
    previous.addEventListener('click', () => move('before'));
    next.addEventListener('click', () => move('after'));
    dialog.querySelector('[data-media-in]').addEventListener('click', () => zoom(scale + .5));
    dialog.querySelector('[data-media-out]').addEventListener('click', () => zoom(scale - .5));
    dialog.addEventListener('keydown', event => {
        if (event.key === 'Tab') {
            const controls = [...dialog.querySelectorAll('button:not(:disabled), a[href]')].filter(control => control.getClientRects().length);
            if ((event.shiftKey && document.activeElement === controls[0])
                || (!event.shiftKey && document.activeElement === controls.at(-1))) {
                event.preventDefault();
                (event.shiftKey ? controls.at(-1) : controls[0])?.focus();
            }
        }
        if (['+', '=', '-', 'ArrowLeft', 'ArrowRight'].includes(event.key)) {
            event.preventDefault();
            event.stopPropagation();
            if (event.key === '-' || event.key === '+' || event.key === '=') zoom(scale + (event.key === '-' ? -.5 : .5));
            else void move(event.key === 'ArrowLeft' ? 'before' : 'after');
        }
    });
    stage.addEventListener('wheel', event => {
        event.preventDefault();
        zoom(scale + (event.deltaY < 0 ? .25 : -.25));
    }, { passive: false });
    const distance = () => {
        const [a, b] = [...pointers.values()];
        return Math.hypot(a.x - b.x, a.y - b.y);
    };
    stage.addEventListener('pointerdown', event => {
        stage.setPointerCapture(event.pointerId);
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        if (pointers.size === 1) start = { x: event.clientX, y: event.clientY, panX: x, panY: y };
        if (pointers.size === 2) pinch = { distance: distance(), scale };
    });
    stage.addEventListener('pointermove', event => {
        if (!pointers.has(event.pointerId)) return;
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        if (pointers.size === 2 && pinch?.distance) zoom(pinch.scale * distance() / pinch.distance);
        else if (scale > 1 && start) {
            x = start.panX + event.clientX - start.x;
            y = start.panY + event.clientY - start.y;
            transform();
        }
    });
    function endPointer(event) {
        if (event.type === 'pointerup' && event.pointerType === 'touch' && pointers.size === 1 && scale === 1 && start
            && event.clientY - start.y > 80 && Math.abs(event.clientX - start.x) < 70) closeDialog();
        pointers.delete(event.pointerId);
        start = pinch = null;
    }
    stage.addEventListener('pointerup', endPointer);
    stage.addEventListener('pointercancel', endPointer);
    dialog.addEventListener('close', () => {
        generation++;
        photo.onload = photo.onerror = null;
        photo.removeAttribute('src');
        pointers.clear();
    });
    viewer = {
        open(item, trigger, type = 'chat') {
            if (type === 'chat' && deleted.has(item.id)) return;
            generation++;
            busy = false;
            gallery = type === 'chat';
            items = new Map([[item.id, item]]);
            exhausted = { before: !gallery, after: !gallery };
            dialog.classList.toggle('modal-announcement', type === 'announcement');
            dialog.classList.toggle('cost-modal', type === 'cost');
            openDialog(dialog, trigger);
            show(item);
        },
        remove(id) {
            deleted.add(id);
            if (!gallery || !items?.has(id)) return;
            items.delete(id);
            if (dialog.open && current === id) closeDialog();
        },
    };
    return viewer;
}
