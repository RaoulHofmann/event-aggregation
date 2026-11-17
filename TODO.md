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
- [ ] Decide whether to support write-back; currently inbound only.

## Follow-up
- [ ] Second provider to validate connector design.
- [ ] Guided onboarding and real-team pilot.
- [ ] Monitoring and backup/restore verification.
- [ ] Always-on worker/external scheduler decision for Fly, which currently scales to zero.
- [ ] User review and publication of the restored cloud draft. Do not publish automatically.
