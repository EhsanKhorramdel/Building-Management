# Phase 5 visual review

Captured with Playwright 1.65.0 using installed Chrome in Chromium mode against the local Laravel server and a SQLite demo database. The managed Chromium download returned HTTP 403 for this location. Both sets use the same browser, viewport sizes and seeded page types.

| Screen | Before light | Before dark | After light | After dark |
| --- | ---: | ---: | ---: | ---: |
| main | 5 | 0 | 5 | 5 |
| login | 5 | 0 | 5 | 5 |
| register | 5 | 0 | 5 | 5 |
| dashboard | 5 | 5 | 5 | 5 |
| building-home | 5 | 0 | 5 | 5 |
| building-announcements | 5 | 0 | 5 | 5 |
| building-poll | 5 | 0 | 5 | 5 |
| building-costs | 5 | 0 | 5 | 5 |
| building-charges | 5 | 0 | 5 | 5 |
| building-payments | 5 | 0 | 5 | 5 |
| building-chat | 5 | 0 | 5 | 5 |
| building-settings | 5 | 0 | 5 | 5 |
| admin | 5 | 0 | 5 | 5 |
| Total | 65 | 5 | 65 | 65 |

Widths are 360, 390, 768, 1024 and 1440 pixels. Heights are 820px at the two phone widths and 900px at the other widths. Files are under `storage/app/test-screenshots/phase-5/before` and `storage/app/test-screenshots/phase-5/after`, named `<screen>-<width>-<theme>.png`. Each width has a guard JSON file. The screenshots and browser test results are ignored by Git.

The final five width tests passed. All 130 after captures had document scrollWidth at or below the viewport width, zero console/page errors, zero visible broken images and zero unlabelled form controls. Each had viewport metadata and the requested theme class. The before set recorded a dashboard listener error at all five widths in both captured themes.

I reviewed all 130 final captures in per-screen contact sheets and checked full-size phone examples and the three supplementary captures. The latter are the 390px poll result in light/dark and the 390px poll editor in dark. The results captures were repeated after the chart animation finished.

## Findings and fixes

| Screen | Finding | Fix and evidence |
| --- | --- | --- |
| Main | The landing page used a separate static stylesheet and nested link/button controls. | Shared Vite assets, direct links, wrapping header and a fluid feature grid. Compare main-360-light.png and main-1440-dark.png. |
| Login | The card reached the viewport edge on phones. Password reveal was an image click target. | Padded fluid card with themed inputs and a labelled keyboard-operable reveal button. Compare login-360-light.png and login-390-dark.png. |
| Register | The card lacked phone gutters and had image-only password toggles. | Fluid card, real buttons, labelled fields and inline errors. Compare register-360-light.png and register-390-dark.png. |
| Dashboard | Persistent narrow sidebar reduced card width. Its dark view retained a peach card. A missing register-button listener caused a console error. | Drawer under 768px, fluid themed card, data attributes for unit actions and guarded module initialization. Compare dashboard-360-light.png and dashboard-390-dark.png. |
| Building home | The sidebar consumed phone space and cards had little room for their actions. | Full-width mobile cards, wrapping stats and actions, desktop grid. Compare building-home-360-light.png and building-home-768-dark.png. |
| Announcements | The list and image dialog used light fixed surfaces. | Themed cards and bounded scrollable dialogs, upload preview and confirmation before cancellation. Compare building-announcements-360-dark.png. |
| Poll | Existing cards and results relied on light colors and the CDN chart. | Theme tokens on cards and Chart.js 4 legend, stroke and data colors. The editor scrolls inside the viewport. See detail-poll-result-390-dark.png and detail-poll-form-390-dark.png. |
| Costs | The invoice and payment controls competed for a narrow phone column. | Wrapping card actions and capped invoice/payment dialogs. Compare building-costs-360-dark.png. |
| Charges | The phone sidebar narrowed the list and modal controls used separate light styles. | Full-width phone cards and themed bounded payment modal with the existing read-only amount. Compare building-charges-360-dark.png. |
| Payments | The old result layout spread data across cards. An initial after capture also caught the loading state before rows arrived. | Scroll inside the table container, print stylesheet, loading/error/empty states. The final capture waits for seeded rows. Compare building-payments-360-light.png and building-payments-1440-dark.png. |
| Chat | The composer fell below the phone viewport. Message navigation, bubbles and menus relied on a tall light layout. | Flex chat shell with an internally scrolling list and reachable composer, themed bubble sides, dates and menus. The demo image retains its own colors. Compare building-chat-360-light.png in both sets. |
| Settings | The narrow column clipped the join-link field and pushed controls below the phone view. | Fluid settings cards, shrinkable link field, wrapping controls and dark surfaces. Compare building-settings-360-light.png in both sets. |
| Admin | The action row squeezed tabs and logout against the viewport edge. | Wrapping controls, padded cards, darker action fills and explicit empty states. Compare admin-360-light.png and admin-390-dark.png. |

The first after pass also caught a CSS specificity conflict that left the closed drawer over content on mobile. Explicit drawer selectors fixed it. The final Playwright run opens the drawer with a real click, navigates each tab and verifies the target panel.

## Browser interaction checks

On the local demo data, the manager opened the unit dialog from its card, closed it with Escape and regained focus on the trigger. Chat send, edit, cancel-delete and confirm-delete passed. A vote submitted successfully and the result chart rendered in both themes. The payments list loaded and print media hid navigation while preserving the table. Announcement cancellation, manager downgrade and admin rejection displayed confirmation and allowed cancellation. The theme persisted across reload and from building to dashboard. A missing poll question reopened the form with the entered title and its field error. A controlled 503 on the report request displayed an error toast, then the toast dismissed itself.

Chrome reported no manifest installability errors in a temporary normal profile. Both manifest icons loaded. Private Pusher subscription/auth configuration was checked in the code. The local demo uses the log broadcaster.

## Repeat the after capture

Use a separate local SQLite file, build assets, run the existing migrations and DemoSeeder, and make the public storage link available. Start the server with the portable PHP binary.

```powershell
& D:\tools\php-8.3\php.exe artisan serve --host=127.0.0.1 --port=8155
```

In another terminal, supply the local demo account values from DemoSeeder.

```powershell
$env:DEMO_MANAGER_EMAIL = '<demo manager email>'
$env:DEMO_ADMIN_EMAIL = '<demo admin email>'
$env:DEMO_PASSWORD = '<demo password>'
$env:PLAYWRIGHT_CHANNEL = 'chrome'
npm run test:visual
```

Leave PLAYWRIGHT_CHANNEL unset to use installed Playwright Chromium. VISUAL_BASE_URL overrides the local URL. VISUAL_STAGE=before selects the historical capture mode when running against the Phase 4 view code.

## Phase 7 map review

The Playwright map spec captures the manager's location picker and the home map after saving a point. It covers 390px and 1440px in light and dark. Captures and the guard JSON files are in `storage/app/test-screenshots/phase-7`.

The eight captures showed the marker, zoom controls and attribution. The mobile map is 260px high with 40px zoom controls. The desktop map is 320px high. Document width stayed within the viewport and there were no console or page errors. Live OpenStreetMap tiles loaded in all eight captures.

The browser flow cleared the fields, selected a point by clicking the map, dragged the marker, typed a coordinate pair, saved it and found the same point on home. The home map and its marker were read-only. A separate browser check submitted coordinates from the registration picker and opened the saved point in the admin's request card before approving it. Denied geolocation left the fields blank and showed a toast. Granted geolocation filled both coordinates.

Use the seeded SQLite setup above and run the map spec against that isolated server. The test changes the demo complex's location.

```powershell
npm run test:visual -- map.spec.js
```

If the default tile host is unavailable, set `MAP_VISUAL_OFFLINE=1` for the map spec. It substitutes transparent tiles from that host and still checks the container, marker, controls, attribution, overflow and console output. Leave this flag off to verify live tiles.
