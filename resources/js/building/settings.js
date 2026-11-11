const defaults = { darkMode: false, viewMode: 'gridView' };

export function readSettings() {
    const cookie = document.cookie.split('; ').find(value => value.startsWith('userSettings='));
    try {
        return { ...defaults, ...JSON.parse(decodeURIComponent(cookie?.slice(13) || '{}')) };
    } catch {
        return { ...defaults };
    }
}

function applySettings(settings) {
    document.documentElement.classList.toggle('dark', settings.darkMode);
    document.querySelector('meta[name=theme-color]')?.setAttribute('content', settings.darkMode ? '#101b20' : '#146457');
    document.querySelectorAll('.mode-switch').forEach(button => button.setAttribute('aria-pressed', String(settings.darkMode)));
    document.querySelectorAll('.building-boxes[data-layout]').forEach(list => {
        list.classList.toggle('jsGridView', settings.viewMode === 'gridView');
        list.classList.toggle('jsListView', settings.viewMode === 'listView');
    });
    for (const [selector, view] of [['.grid-view', 'gridView'], ['.list-view', 'listView']]) {
        document.querySelectorAll('.view-actions ' + selector).forEach(button => {
            button.classList.toggle('active', settings.viewMode === view);
            button.setAttribute('aria-pressed', String(settings.viewMode === view));
        });
    }
    document.dispatchEvent(new Event('themechange'));
}

const building = JSON.parse(document.querySelector('#building-config')?.textContent || 'null');
const settings = readSettings();
if (building) settings.viewMode = building.initialLayout;
applySettings(settings);

function changeSetting(key, value) {
    settings[key] = value;
    if (building && key === 'viewMode') {
        document.dispatchEvent(new CustomEvent('building-layout-selected', { detail: value }));
        applySettings(settings);
        return;
    }
    const cookieSettings = building ? { ...readSettings(), darkMode: settings.darkMode } : settings;
    document.cookie = 'userSettings=' + encodeURIComponent(JSON.stringify(cookieSettings)) + '; path=/; SameSite=Lax';
    // Older building pages wrote this cookie at the default building path.
    document.cookie = 'userSettings=; Max-Age=0; path=/dashboard/building; SameSite=Lax';
    applySettings(settings);
}

document.addEventListener('click', event => {
    if (event.target.closest('.mode-switch')) changeSetting('darkMode', !settings.darkMode);
    if (event.target.closest('.view-actions .grid-view')) changeSetting('viewMode', 'gridView');
    if (event.target.closest('.view-actions .list-view')) changeSetting('viewMode', 'listView');
});
