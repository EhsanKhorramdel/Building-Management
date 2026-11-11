export function avatar(name, url) {
    const badge = document.createElement('span');
    badge.className = 'user-avatar';
    badge.setAttribute('aria-hidden', 'true');
    badge.textContent = Array.from(name)[0] || '';
    if (url) {
        const image = document.createElement('img');
        image.alt = '';
        image.src = url;
        image.addEventListener('error', () => image.remove(), { once: true });
        badge.append(image);
    }
    return badge;
}
