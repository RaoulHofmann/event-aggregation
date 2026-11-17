# EventNest roadmap

Goal: create native EventNest events and connect external hosts, with one management interface and API. Humanitix is the first provider.

## Restored foundation
- [x] PHP 8.5 / Laravel 13 and compatible Inertia, Swagger and PHPUnit dependencies.
- [x] Atomic registration and unique workspace schema provisioning.
- [x] Membership validation, safe schema selection/reset and cross-workspace regression tests.
- [x] Token read permissions and corrected template-name API filtering.
- [x] Canonical event fields alongside existing custom data, with additive tenant migrations.

## Restored UI
- [x] Responsive workspace sidebar and account/team navigation.
- [x] Real dashboard metrics, recent events and creation links.
- [x] Event search, source/template filters, pagination and create/edit/delete flows.
- [x] Template/field management, validation states and protected provider-managed data.
- [x] Humanitix connection management and masked credential entry.
- [x] Landing page and authentication branding.
- [ ] Further polish of account/team settings interiors.

## Humanitix and API
- [x] Encrypted credentials, owner-only management, no credential serialization/flashing.
- [x] Paginated HTTP adapter and canonical field mapping.
- [x] Idempotent imports, queued jobs, retries and safe terminal failure status.
- [x] Scheduled refreshes and visible sync errors/status.
- [x] Disconnects clear keys; imported records remain; locally hidden imports stay hidden.
- [x] Versioned API with pagination, source/status/date filters and rate limiting.
- [ ] Validate with an authorized Humanitix account and current official documentation.
- [ ] Confirm cancellation, deletion and recurring-event semantics; add webhooks/incremental sync.
- [x] Humanitix is GET-only by requirement; no write-back. Existing-template mapping and protected local edits are supported.

## Follow-up
- [ ] Second provider to validate connector design.
- [ ] Guided onboarding and real-team pilot.
- [ ] Monitoring and backup/restore verification.
- [ ] Always-on worker/external scheduler decision for Fly, which currently scales to zero.
- [ ] User review and publication of the restored cloud draft. Do not publish automatically.

## Calendar and event editor
- [x] Month/week/agenda/table views, date navigation, timezone selection, details and undated view.
- [x] Optional templates for basic events, starter-template shortcuts, honoured column layouts and safer template switching.
- [x] Finite native recurrence with preview, local wall-clock scheduling, count/until and stable occurrence API identities.
- [x] Skip/reschedule a single occurrence; protect provider imports and existing series exceptions.
- [ ] Guided migration of legacy custom dates/recurrence after timezone confirmation; original data remains intact.
- [ ] “This and following” series splitting and exception undo/history UI.
- [ ] Public embeds/ICS subscriptions and saved calendar preferences.

## Editor usability and homepage studies
- [x] Wide template editor with field search, column placement and live layout preview; wider custom-field editor with sample control preview.
- [x] Three responsive public homepage studies at /designs/homepage; current homepage retained for comparison.
- [x] Noticeboard selected and promoted to the main homepage, with API examples/docs and WIP/free-access disclosure.

- [x] Template-aware event editor with section navigation, wider layout, local import notices and validation routing.
- [x] Minimal dashboard retained with subtle warm brand accents.

## Visual refinement
- [x] Softer homepage palette, reduced-motion-aware event-card animation, interactive template example and fewer registration calls to action.
- [x] Pricing page: currently free access/usage plus two greyed-out TBD future plans.
- [x] Neutral event modal and a dashboard with restrained colour, useful dates and richer event/API panels.

- [x] Restore Noticeboard character while keeping large background colours restrained; replace entrance-only motion with a cycling event-to-API demo, pause/manual controls and reduced-motion support.
