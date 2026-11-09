# Findings

The audit reviewed the accepted Phase 8 build. These entries are for owner review. Effort estimates use S for a small localized change, M for coordinated changes or tests, and L for work across several areas.

## Audit results

| Check | Result |
| --- | --- |
| PHP suite | 387 passed, 0 failed, 0 skipped, 1,102 assertions |
| Frontend build | Passed |
| Authorization matrix | 168 passed, 242 assertions |
| Current browser sweep | 25 states × 5 widths × 2 themes = 250 captures. 201 passed all raw guards and 49 had a flag. |
| Overflow and images | 0 page-width overflows, 0 broken images and 0 pending visible images. |
| Console and touch | One capture logged a 404. 48 captures reported targets under 40px at 360, 390 or 768px, including six reports of the visually hidden chat input. Its visible label passed. |
| Automated accessibility | axe-core 4.14.0, WCAG 2 and 2.1 A/AA tags, 25 desktop light states. Three serious occurrences of the same rule, zero critical, moderate or minor violations. |
| Browser flows | 11 of 13 steps passed on the first chained run. Poll creation failed and left no poll to vote on. Voting passed in a separate run on an existing poll. |
| Performance | Payments report used six queries at 2, 8 and 32 units. Building dashboard used 11 and user dashboard used two at each size. See the measured comparison. |
| Secret scan | Zero matches in the current scope. The original public client-key literal matched in the old browser script. |

The sweep ran at 360, 390, 768, 1024 and 1440px in light and dark. It covers landing, sign-in, account registration, password reset, password confirmation, email verification, dashboard home/settings/building registration, every building tab, map picker and display, three admin tabs, mock checkout and online payment history. Captures and the per-screen `guards.json` are under `storage/app/test-screenshots/phase-8b`. The appendix data `phase8b-axe.json` lists rule and element details. An automated scan is a partial check of accessibility.

## Browser click-throughs

| Flow | Result | Observation |
| --- | --- | --- |
| Register | PASS | New account signed in and reached the dashboard. |
| Request a complex | PASS | Submitted without coordinates and received confirmation. |
| Admin approve | PASS | The request moved from pending to accepted. |
| Assign unit | PASS | The new manager attached unit 1 through the dashboard card. |
| Set map | PASS | The saved point rendered on the home tab. |
| Announcement with upload | PASS | The uploaded PNG opened and decoded in its preview. |
| Create charge | PASS | A charge spanning the current Jalali month was published. |
| Manual pay | PASS | The flags-off form recorded the payment and showed success. |
| Mock pay | PASS | A second charge paid through the mock checkout and returned with a reference. |
| Create poll | FAIL | An hours-only form submission returned a local TypeError page. A retry with all three fields filled also returned HTTP 500. |
| Vote | PASS on retry | The chained attempt could not find a newly created poll. A separate attempt on a seeded open poll returned 302 and a success toast. |
| Chat send, edit and delete | PASS | The message appeared, reflected the edit, and disappeared after confirmed deletion. |
| Payments print | PASS | The button invoked printing. Print media hid the header and preserved the report table. |

Detailed results are in `flows.json` and `flow-poll-retry.json` beside the screenshots.

## Issues for review

| ID | Severity | Where and evidence | Suspected cause | Suggested fix | Effort |
| --- | --- | --- | --- | --- | --- |
| F01 | High | Poll creation at `app/Services/PollService.php:32`. `flow-create-poll-failure.png` shows a Carbon type error. `flow-poll-duration-retry.png` and the response probe show HTTP 500 with all fields entered. `flow-vote-failure.png` shows the chained run with no poll available. | The form request validates integers but returns the HTTP form values as strings. The service also passes null for omitted duration parts. Carbon 3 requires numeric PHP values here. The passing poll feature test sends integers directly. | Normalize each validated duration to an integer, using zero for omitted parts. Add form-encoded regression cases with one duration and with all three. | S |
| F02 | Medium | Chat history. The original `building/group.blade.php` and `public/js/building.js` define `scrollToBottomBtn` and load older messages near the top. Current `building-chat-390-light.png` and `resources/js/building/chat.js` show explicit paging controls. | The browser module replaced scroll-driven paging and dropped the jump-to-latest control. | Restore an accessible jump-to-latest action and decide whether to keep paging explicit or also trigger it near the top. | M |
| F03 | Medium | Shared guest logo link. axe `link-name` reported one serious occurrence on each of `reset-password-1440-light.png`, `confirm-password-1440-light.png` and `verify-email-1440-light.png`. | The SVG-only home link has no accessible name. | Give the link a localized name and treat the icon as decorative. | S |
| F04 | Low | Guest layout. `confirm-password-1440-light.png` logged a 404 for `/favicon.ico`. | The shared guest layout does not specify the project's favicon, so the browser falls back to the default path. | Reference the existing favicon in the shared page head. | S |
| F05 | Low | Remember-me control on `login-{360,390,768}-{light,dark}.png`. The native checkbox is 16×16px. At 390px the associated clickable label is 121.5×20px. | The label wraps its content but has no minimum height. | Give the labelled row a 40px minimum touch height. | S |
| F06 | Low | Verify-email logout on `verify-email-{360,390,768}-{light,dark}.png`. The button is 31.3×40px at 390px. | The text-only button does not reserve a minimum width. | Apply the shared action sizing while retaining POST logout. | S |
| F07 | Low | Map attribution links on `building-registration`, `building-home`, `building-building-settings`, `map-display` and `map-picker` captures at all three touch widths in both themes. The links are 14px high at 390px. | Leaflet attribution uses its compact inline default. | Increase the clickable area while keeping provider attribution visible and in the map. | S |
| F08 | Low | Map zoom buttons on the same five map states at 768px, in both themes. The 768px light probes measured 30×30px. | The 40px CSS override stops below 768px. | Apply touch-sized controls to tablets and coarse pointers. | S |
| F09 | Low | Draggable pin on `building-building-settings` and `map-picker` captures at all three touch widths in both themes. The 390px pin is 25×41px. | The touch area is the width of the marker image. | Expand the draggable hit area while keeping the visual pin and its coordinate anchor. | M |
| F10 | Low | Display-only map pin on `building-home` and `map-display` captures at all three touch widths in both themes. The marker exposes button semantics and measures 25×41px. | The read-only marker uses Leaflet's default interactive marker options without a corresponding action. | Remove interactive semantics from display-only pins and retain an accessible text description of the map. | S |
| F11 | Low | Touch guard noise on `building-chat-{360,390,768}-{light,dark}.png`. The raw guard selected a 1px-wide hidden file input. The visible attachment label measured 40×40px at 390px. | The guard measured an input styled for screen readers instead of its visible proxy. | Measure the labelled attachment control in the audit guard and keep checking that keyboard and assistive input remain available. | S |
| F12 | Low | Manual payments report on `building-payments-390-light.png`. The print flow passed, but users must discover the table's horizontal scroll to see later columns. | The report correctly contains its overflow but offers little visual cue that more columns are available. | Add a localized scroll cue or a compact mobile row layout. | S |
| F13 | Medium | Chat attachments. An anonymous GET of the seeded attachment at `/storage/chats/demo-message.png` returned HTTP 200 with `image/png`. The authenticated chat shows that image in `building-chat-390-light.png`. | Files are served by the public storage link, outside the complex membership check used for chat metadata. A copied attachment URL can be opened without a session. | Route chat attachment delivery through the same membership policy and plan a migration of existing files and URLs. | M |

Totals are **1 high, 3 medium and 9 low**. F11 is a measurement issue rather than a failed visible target. Screenshot patterns denote both themes at the listed widths.
