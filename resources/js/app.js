import './bootstrap';
import './ui';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.querySelector('[data-print-receipt]')?.addEventListener('click', () => window.print());
