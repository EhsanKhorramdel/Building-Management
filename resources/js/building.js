import './ui';
import { config } from './building/api';
import { navigation } from './building/navigation';
import { announcements } from './building/announcements';
import { chat } from './building/chat';
import { payments } from './building/payments';
import { polls } from './building/poll';
import { uploader } from './building/uploader';
import { maps } from './building/map';

announcements();
chat();
payments();
polls();
uploader();
maps();
document.querySelector('[data-map-settings]')?.addEventListener('click', event => {
    event.preventDefault();
    document.querySelector('[data-tab="setting"]').click();
});
navigation(config.initialPanel);
