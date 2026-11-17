# Event overview, creation, recurrence and template review

Reviewed 17 November 2026 against commit d5c7407. This is a source-code review and proposed implementation scope; no production behaviour has changed. Trumba is a product direction supplied by the user, not a claim of verified feature parity or a review of its current website.

## Findings

1. **Repeating events are not implemented.** The legacy DefaultEventTemplateSeeder declares frequency, interval, weekdays, end date, count and exclusion fields. There is no series/occurrence model or recurrence expansion service. Saving those custom values does not generate dates, and the canonical API has no recurrence contract. The seeder targets the fixed test_user_team schema and is not called by normal workspace provisioning; it is not a safe workspace starter.
2. **There are two competing sets of standard fields.** The event form has canonical title, schedule, description, location, status and timezone, but also renders all selected custom fields. The old default template duplicates several of these. The canonical migration only backfills title/name, so an old event_data.start_date is not automatically a calendar starts_at. Existing records need an explicit mapping/preview with timezone handling and ambiguous records flagged for review; do not silently reinterpret dates or drop JSON.
3. **The overview is a template-grouped table.** It does not consistently expose canonical date/time, location and status. Date navigation, month/week/agenda views and event details are missing. Pagination happens separately in each template group. EventController.index loads the entire event collection, so adding an unbounded calendar on top would scale poorly.
4. **Creation requires configuration before a first event.** Every event needs a template with at least one custom field; new workspaces get neither starter fields nor templates. Schedule is hidden in a collapsed section and an event can have no date. Switching templates clears all custom answers without warning. There is no occurrence preview, all-day support or duplicate-event action.
5. **Template layout is saved but ignored by creation.** WorkspaceCollection writes left/right columns; Events/Handler renders fields in a generic grid using fields order. Template validation does not check that layout IDs match selected fields, or exclude provider-managed/soft-deleted fields. Custom field validation_rules are stored but not applied by EventController. Legacy checkbox/file/multiple definitions are not supported consistently by the current renderer/validator.
6. **Imported records must retain their ownership boundary.** Humanitix events are locally read-only and hidden deletions survive sync. A calendar must retain these rules; provider recurrence must be mapped from verified source semantics rather than inferred from matching event titles.

## Recommended experience

- A shared Events workspace with Month, Week, Agenda and Table switches, Today/previous/next navigation, date picker, persistent search/source/template/status filters and a clear display timezone. Agenda is the mobile default. Show multi-day and all-day events correctly, accessible overflow lists for busy dates, a separate undated section, and cancelled/draft styling beyond colour alone.
- Select an event for a details panel with schedule, timezone, venue, description, custom information and source link. Native events offer edit/duplicate; imports link to the source. Selecting a calendar date pre-fills creation.
- A spacious editor with Basics, Schedule and repeat, Location, Custom fields and Preview. Keep schedule visible. Permit undated drafts, require a schedule to publish, and distinguish all-day dates from UTC instants. Make save draft/publish intent clear.
- Standard fields are built in. Templates add event-specific questions and useful defaults, for example Workshop, Community event and Performance. A basic event must work without custom field setup. Template selection should preview fields, preserve compatible answers, warn before discarding data and honour layout. Do not relabel templates as calendars: calendars/collections and event types serve different purposes.

## Recurrence contract

Store a native series with a local start, IANA timezone, duration/all-day semantics, recurrence rule and stable identity. Resolve occurrences for a bounded date window, preserving local wall-clock time across daylight-saving changes. Expose stable occurrence IDs and original scheduled start through an additive API endpoint/contract; preserve existing event IDs and API behaviour.

Support daily, weekly on chosen weekdays, monthly by date or weekday position, and yearly patterns, every N periods, ending by date or count. Make month-end and leap-day policy explicit. Show a human-readable summary and upcoming occurrence preview before save. Add occurrence exceptions for skipping, cancellation and rescheduling. Offer “this occurrence” and “entire series”; implement “this and following” as an explicit series split once identity and exception handling are tested. Imports retain provider identities and source authority.

## Implementation order and acceptance

1. Canonical-field migration plan and validation; basic/starter templates; honour template layout; safe template switching. No loss of existing custom JSON or provider data.
2. Indexed, workspace-scoped date-window queries using interval overlap, bounded calendar/agenda results and a separately paginated undated view; month/agenda UI, event details and date-prefilled creation. Test boundaries, cross-midnight/multi-day events, timezone display, cancellation, mobile and keyboard use.
3. Series/occurrence backend and API, repeat editor, preview and exceptions. Test DST transitions, leap years, month ends, count/until bounds, moved exceptions crossing query windows, overlapping events, workspace isolation and stable occurrence identity. Monthly/yearly support must not be advertised until these semantics pass.
4. Week view, saved calendar filters, public calendar/embeds and ICS subscriptions if desired. Choose export IDs and recurrence semantics before feeds are public.

Use one occurrence resolution service for calendar and API so dates agree. Keep query windows and expansion bounded; do not generate infinite series or depend on an awake Fly scheduler to discover occurrences. A Vue calendar library can supply accessible date-grid interactions, but recurrence and provider ownership belong to the backend.
