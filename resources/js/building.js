import './ui';
import { config, request } from './building/api';
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
let preferenceWrite = Promise.resolve();
function savePreference(data) {
    preferenceWrite = preferenceWrite.then(() => request(config.preferencesUrl, { method: 'PATCH', data, json: false }));
}
document.addEventListener('building-panel-selected', event => savePreference({ panel: event.detail }));
document.addEventListener('building-layout-selected', event => savePreference({ layout: event.detail }));
navigation(config.initialPanel);
if (config.persistInitialPanel) savePreference({ panel: config.initialPanel });
