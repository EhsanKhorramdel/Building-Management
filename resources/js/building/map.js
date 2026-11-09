import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png?url';
import markerIconRetina from 'leaflet/dist/images/marker-icon-2x.png?url';
import markerShadow from 'leaflet/dist/images/marker-shadow.png?url';
import '../../css/map.css';
import { toast } from '../ui';

const tehran = [35.6892, 51.3890];
const pin = L.icon({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIconRetina,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    shadowSize: [41, 41],
});

function coordinate(value, limit) {
    const text = value.trim();
    if (!/^[+-]?(?:\d+\.?\d*|\.\d+)(?:e[+-]?\d+)?$/i.test(text)) return null;
    const number = Number(text);
    return Math.abs(number) <= limit ? number : null;
}

function pair(latitude, longitude) {
    const lat = coordinate(latitude, 90);
    const lng = coordinate(longitude, 180);
    return lat !== null && lng !== null ? L.latLng(lat, lng) : null;
}

function createMap(element) {
    const picker = element.closest('[data-coordinate-picker]');
    const latitude = picker?.querySelector('[name=latitude]');
    const longitude = picker?.querySelector('[name=longitude]');
    const initial = pair(latitude?.value ?? element.dataset.latitude, longitude?.value ?? element.dataset.longitude);
    const map = L.map(element, { zoomControl: false, dragging: Boolean(picker), scrollWheelZoom: false })
        .setView(initial ?? tehran, initial ? 15 : 11);
    L.control.zoom({ zoomInTitle: 'بزرگ‌نمایی', zoomOutTitle: 'کوچک‌نمایی' }).addTo(map);
    L.tileLayer(element.dataset.tileUrl, {
        attribution: element.dataset.tileAttribution,
        maxZoom: 19,
    }).addTo(map);

    let marker;
    function write(position) {
        latitude.value = position.lat.toFixed(7);
        longitude.value = position.lng.toFixed(7);
    }

    function place(position) {
        if (marker) {
            marker.setLatLng(position);
        } else {
            marker = L.marker(position, { icon: pin, draggable: Boolean(picker), alt: 'موقعیت ساختمان' }).addTo(map);
            if (picker) {
                marker.on('dragend', () => {
                    const location = marker.getLatLng().wrap();
                    marker.setLatLng(location);
                    write(location);
                });
            }
        }
    }

    if (initial) place(initial);
    if (!picker) return map;

    map.on('click', event => {
        const position = event.latlng.wrap();
        place(position);
        write(position);
    });

    function clearMarker() {
        marker?.remove();
        marker = null;
    }

    function readInputs() {
        const position = pair(latitude.value, longitude.value);
        if (!position) {
            clearMarker();
            return;
        }
        place(position);
        map.setView(position, Math.max(map.getZoom(), 15), { animate: false });
    }

    latitude.addEventListener('input', readInputs);
    longitude.addEventListener('input', readInputs);
    picker.querySelector('[data-map-clear]').addEventListener('click', () => {
        latitude.value = longitude.value = '';
        clearMarker();
    });
    picker.querySelector('[data-map-locate]').addEventListener('click', () => {
        const unavailable = () => toast('دسترسی به موقعیت فعلی ممکن نیست.', 'error');
        if (!navigator.geolocation) {
            unavailable();
            return;
        }
        navigator.geolocation.getCurrentPosition(({ coords }) => {
            const position = L.latLng(coords.latitude, coords.longitude);
            place(position);
            write(position);
            map.setView(position, 15);
        }, unavailable, { timeout: 10000 });
    });

    return map;
}

export function maps() {
    for (const element of document.querySelectorAll('[data-complex-map]')) {
        let map;
        // Hidden tabs and dialogs have no dimensions until they open.
        const observer = new ResizeObserver(([entry]) => {
            if (!entry.contentRect.width || !entry.contentRect.height) return;
            if (map) map.invalidateSize();
            else map = createMap(element);
        });
        observer.observe(element);
    }
}
