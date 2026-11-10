export function smallTouchTargets() {
    const visible = element => Boolean(element.getClientRects().length && getComputedStyle(element).visibility === 'visible');
    const selector = 'a[href], button, summary, input:not([type=hidden]), textarea, select, [role=button]';
    const targets = new Set();

    for (const element of document.querySelectorAll(selector)) {
        if (!visible(element) || element.disabled) continue;
        const usesLabel = element instanceof HTMLInputElement
            && (element.classList.contains('sr-only') || ['checkbox', 'radio'].includes(element.type));
        const proxy = usesLabel && Array.from(element.labels || []).find(label => visible(label) && !label.classList.contains('sr-only'));
        targets.add(proxy || element);
    }

    return [...targets].flatMap(element => {
        const { width, height } = element.getBoundingClientRect();
        if (width >= 40 && height >= 40) return [];

        return [{
            tag: element.tagName.toLowerCase(),
            id: element.id,
            name: (element.getAttribute('aria-label') || element.textContent.trim() || element.getAttribute('name') || '').slice(0, 65),
            width: Math.round(width * 10) / 10,
            height: Math.round(height * 10) / 10,
        }];
    });
}
