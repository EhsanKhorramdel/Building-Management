# Changelog

See [REFACTOR_LOG.md](docs/REFACTOR_LOG.md) for the engineering record and verification details.

## [Unreleased]

### Added

- Live building chat delivery without a reload is verified with two signed-in browser sessions. The live check can be run on demand.
- Chat has reactions, replies, manager pins and moderation, bulk deletion, normalized text search, typing indicators and live seen counts. Deleted rows keep a marker in the thread.
- Chat, announcement and invoice images open in the same accessible viewer. Chat images support gallery navigation across message pages.
- Managers can pin, archive, restore and search announcements and see who read them. Polls support early closure, pre-vote edits, current-choice preselection and manager-only named results after closure.
- Completed payment reports can be filtered, printed and exported as CSV. Authorized users can open printable receipts. Staff can review redacted payment attempts and their timelines, and payers can page through online history.
- Profiles support private avatars and notification choices. Buildings remember each account's tab and unit layout. Staff have scoped audit details, managers can issue expiring or single-use invites, and buildings have a read-only Jalali calendar.

### Fixed

- Chat menus stay reachable on phones and desktops, close on outside interaction or Escape, and return keyboard focus. The mobile drawer traps focus and short screens keep the chat composer and dialog controls reachable.
- Both location tables reject unpaired or out-of-range coordinates.

### Security

- Chat viewer names are limited to the author and the complex's managers.
- Announcement images and incidental-cost invoices require building membership or global admin access, including when someone opens a copied image URL.

## [1.0.0] — post-refactor baseline (2026-11-10)

### Added

- Local demo data for administrators, managers and residents.
- A printable payment report, install metadata and clearer empty and loading states.
- Optional map locations during building registration, admin review, and manager settings.
- Optional online payment checkout and SMS notifications. Both ship disabled.
- Recent activity for global administrators and building managers, including role changes, membership decisions and invite rotations.
- One command runs the browser checks against fresh test fixtures, including mock payments, chat, polls and map settings.

### Changed

- Phone layouts use a drawer, fit forms and dialogs to the viewport and keep the chat composer in reach.
- Dark mode covers the building, chat, account and admin screens while keeping existing preferences.
- Payment reports use fewer queries as complexes grow. Poll results are counted in the database.
- Forms show a shared error and success display, preserve input on validation errors and confirm destructive actions.
- The application runs on Laravel 12.72 and PHP 8.3 or a newer PHP 8.x release. The building workflows have regression coverage.
- Numeric forms accept Persian and Arabic-Indic digits and preserve leading zeroes in identifiers. Signing out takes users to the public home page.

### Fixed

- Expired polls reject votes, and blank options no longer hide later options.
- Empty chat opens normally, and profile updates preserve omitted optional fields.
- Charge payments can be recorded again in a new Jalali month, while duplicate payments in the same period are blocked. Building approval creates its records together.
- Chat can return to the latest message after paging back. Payment reports show when more columns are available on a phone.

### Security

- Building pages, actions and live chat enforce membership in the target complex. Managers can rotate an invite to invalidate the previous one.
- Payment amounts come from stored charges and costs. Chat rejects unsupported or oversized image uploads.
- Destructive actions require protected requests. The chat viewer list renders names as text.
- Chat attachments require authorization even when someone opens the image URL directly.

### Open items

- Live Pusher delivery was pending at release. The live browser check now passes under [Unreleased](#unreleased).
- Full MySQL test coverage remains open. MySQL 8.4.13 passed migration, one rollback step, fresh migration and demo seeding. The test harness requires in-memory SQLite and stopped all 437 feature cases during bootstrap.
- Announcement and incidental-cost images used public storage at release. Attachment privacy is now closed under [Unreleased](#unreleased).
- Post-refactor backlog is tracked in the items marked Later. [Proposals](docs/PROPOSALS.md)
