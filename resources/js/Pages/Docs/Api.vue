<script setup>
import EventJunctionMark from "@/Components/EventJunctionMark.vue";
import { Head } from "@inertiajs/vue3";
import ApiExample from "@/Components/ApiExample.vue";
</script>
<template>
    <Head title="EventJunction API documentation" />
    <div class="api-docs">
        <header>
            <a href="/" class="brand">
                <EventJunctionMark class="inline size-7 align-middle" /> EventJunction
            </a>
            <nav>
                <a href="/">Home</a><a href="/user/api-tokens">API access</a
                ><a href="/register">Get started</a>
            </nav>
        </header>
        <main>
            <p class="eyebrow">DEVELOPER DOCUMENTATION / V1</p>
            <h1>One API for<br />your event programme.</h1>
            <p class="lead">
                Read native and imported events from the same workspace. Use
                event records for listings, and occurrences for a calendar that
                includes repeating dates and local changes.
            </p>
            <p class="mt-3">
                <a :href="route('l5-swagger.default.api')" class="font-semibold underline">Open the Swagger API reference</a>
            </p>
            <p class="release-note">
                EventJunction is a work in progress. Access and usage are currently
                free. Future pricing is to be determined; limits and features
                may change as the service develops.
            </p>
            <div class="docs-layout">
                <aside>
                    <nav aria-label="Documentation sections">
                        <a href="#authentication">Authentication</a
                        ><a href="#examples">Request examples</a
                        ><a href="#events">Event records</a
                        ><a href="#occurrences">Calendar occurrences</a
                        ><a href="#behaviour">Source & recurrence behaviour</a
                        ><a href="#errors">Limits & errors</a>
                    </nav>
                </aside>
                <div class="docs-content">
                    <section id="authentication">
                        <h2>Get connected</h2>
                        <ol>
                            <li>
                                Create an account and select the workspace you
                                want to read.
                            </li>
                            <li>
                                Open
                                <a href="/user/api-tokens">API access</a> and
                                create a key with <strong>All API data</strong> or
                                <strong>Events and occurrences only</strong>.
                            </li>
                            <li>
                                Store that token in your application’s
                                environment as <code>EVENTJUNCTION_TOKEN</code>.
                                Send it as
                                <code>Authorization: Bearer &lt;token&gt;</code
                                >, with <code>Accept: application/json</code>.
                            </li>
                        </ol>
                        <p>
                            All-data read keys use the account’s current workspace.
                            Events-only keys and write-enabled keys use the workspace
                            selected when the key was created. Membership is checked
                            on every request. Keep
                            tokens server-side; a website should fetch data
                            through its own backend rather than expose a token
                            in public JavaScript.
                        </p>
                    </section>
                    <section id="examples">
                        <h2>Make your first request</h2>
                        <p>
                            These examples use this site’s base URL. The
                            responses are illustrative excerpts, not live
                            account data. Replace
                            <code>EVENTJUNCTION_TOKEN</code> in your environment
                            before running them.
                        </p>
                        <ApiExample />
                    </section>
                    <section id="events">
                        <h2>Event records</h2>
                        <code class="endpoint">GET /api/v1/events</code>
                        <p>
                            Returns source event records, including series
                            definitions. Omit the source filter to return native
                            and imported events together. Each record identifies
                            its provider in source metadata. It does not expand
                            repeats. Responses include <code>data</code>,
                            pagination <code>links</code>, and
                            <code>meta</code>.
                        </p>
                        <div class="table-scroll">
                            <table>
                                <caption class="sr-only">
                                    Event query parameters
                                </caption>
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Usage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>status</td>
                                        <td>
                                            <code>draft</code>,
                                            <code>published</code>, or
                                            <code>cancelled</code>. Use
                                            <code>published</code> for a public
                                            listing; authenticated results
                                            otherwise include drafts.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>source</td>
                                        <td>
                                            <code>eventnest</code> (native EventJunction events),
                                            <code>humanitix</code>,
                                            <code>google_calendar</code>,
                                            <code>ics</code>, <code>csv</code>,
                                            <code>wordpress</code>, or
                                            <code>eventbrite</code>.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>starts_after / starts_before</td>
                                        <td>
                                            Inclusive date/time bounds on the
                                            source event’s start. For repeating
                                            dates, use the occurrence endpoint
                                            instead.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>per_page / page</td>
                                        <td>
                                            1–100 records per page (default 25);
                                            page numbers start at 1.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p>
                            Records include title, description, schedule, IANA
                            timezone, location, status, source metadata,
                            template, custom fields, all-day status and the
                            recurrence definition.
                        </p>
                    </section>
                    <section id="occurrences">
                        <h2>Calendar occurrences</h2>
                        <code class="endpoint">GET /api/v1/occurrences</code>
                        <p>
                            Supply <code>from</code> and <code>to</code> as ISO
                            dates or timestamps. The end is exclusive, and a
                            window can cover up to 93 days. Results include any
                            event overlapping that window, including moved
                            occurrences that enter it, sorted by start time.
                        </p>
                        <p>
                            Optional filters are <code>search</code> (title),
                            <code>source</code>, <code>template</code> (ID), and
                            <code>status</code>. The response is
                            <code>{ "data": [...] }</code>, without pagination.
                        </p>
                        <p>
                            Each occurrence has a stable ID such as
                            <code>42@2026-11-21</code>: the source event ID and
                            its original local date. Rescheduling preserves that
                            identity. The response also includes the actual UTC
                            start/end, local dates, event timezone, source,
                            custom fields, status and whether it repeats.
                            All-day end dates are exclusive.
                        </p>
                    </section>
                    <section id="behaviour">
                        <h2>How sources and repeats behave</h2>
                        <p>
                            Connected hosts are read-only integrations.
                            Connected providers supply events through
                            GET-only calendar/event requests. You can map
                            imports to your templates and edit them locally.
                            Changed fields are protected on later syncs;
                            schedule fields are protected together, as are
                            custom answers and their template. Deleting an
                            import hides it locally.
                        </p>
                        <p>
                            Native recurrence supports daily, weekly, monthly
                            and yearly patterns, intervals, a finite occurrence
                            count (up to 1,000), and an optional inclusive local
                            end date. Monthly patterns can use a date or an
                            ordinal weekday. Invalid month-end/leap-day dates
                            and nonexistent daylight-saving times are skipped.
                            Rules scan at most 1,000 frequency periods.
                        </p>
                        <p>
                            Timed repeats keep local wall-clock starts and
                            elapsed durations. All-day repeats keep local-day
                            durations. Single occurrences can be skipped or
                            rescheduled in EventJunction. Google Calendar repeats
                            arrive as individual editable occurrences within the
                            import window. Further provider recurrence mapping,
                            exception undo and “this and following” edits remain
                            in development.
                        </p>
                    </section>
                    <section id="errors">
                        <h2>Limits and errors</h2>
                        <ul>
                            <li>
                                60 requests per minute per authenticated user
                                for the versioned API. A rate-limit response
                                uses HTTP 429.
                            </li>
                            <li>
                                Occurrence queries are limited to 500 candidate
                                event records and 5,000 returned occurrences.
                                Narrow the filters or date range if you receive
                                HTTP 422.
                            </li>
                            <li>
                                Invalid parameters return HTTP 422.
                                Missing/invalid authentication uses HTTP 401;
                                insufficient permission or workspace access uses
                                HTTP 403.
                            </li>
                            <li>
                                Timestamps are returned in UTC. Use the supplied
                                event timezone for local display.
                            </li>
                        </ul>
                        <p>
                            Read-only keys are the default. To create or update events,
                            templates or custom fields, grant the corresponding write
                            permission in API Access. Write keys and events-only keys
                            use the workspace selected when the key was created.
                            See the <a :href="route('l5-swagger.default.api')" class="underline">Swagger reference</a>
                            for request bodies and responses.
                        </p>
                    </section>
                </div>
            </div>
        </main>
        <footer>
            <a href="/">EventJunction</a
            ><span>Work in progress · Currently free · Future pricing TBD</span>
            <a href="/privacy-policy">Privacy</a>
            <a href="/terms-of-service">Terms</a>
        </footer>
    </div>
</template>
<style scoped>
.api-docs {
    background: #fff4e4;
    color: #27231f;
    font:
        18px/1.8 Arial,
        Helvetica,
        sans-serif;
    min-height: 100vh;
}
.api-docs a {
    color: inherit;
    text-underline-offset: 4px;
}
.api-docs header {
    padding: 24px 6vw;
    border-bottom: 2px solid #27231f;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}
.brand {
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -1.5px;
    text-decoration: none;
}
.api-docs header nav {
    display: flex;
    gap: 24px;
    font-size: 16px;
}
.api-docs main {
    max-width: 1440px;
    margin: auto;
    padding: 55px 6vw;
}
.eyebrow {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.1em;
}
.api-docs h1 {
    font-size: clamp(40px, 5vw, 66px);
    line-height: 1.03;
    font-weight: 900;
    letter-spacing: -2.5px;
    margin: 22px 0;
}
.lead {
    max-width: 820px;
    font-size: 21px;
}
.release-note {
    border: 2px solid #27231f;
    background: #f5be28;
    padding: 18px 24px;
    margin: 30px 0 50px;
    font-size: 16px;
}
.docs-layout {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 55px;
}
.docs-layout aside {
    position: sticky;
    top: 25px;
    align-self: start;
}
.docs-layout aside nav {
    display: flex;
    flex-direction: column;
    gap: 12px;
    font-size: 15px;
}
.docs-content {
    min-width: 0;
}
.docs-content section {
    border-top: 2px solid #27231f;
    padding-top: 25px;
    margin-bottom: 45px;
    scroll-margin-top: 25px;
}
.api-docs h2 {
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -0.8px;
    line-height: 1.2;
    margin: 0 0 22px;
}
.api-docs p {
    margin-bottom: 22px;
}
.api-docs ol {
    list-style: decimal;
    padding-left: 24px;
}
.api-docs ul {
    list-style: disc;
    padding-left: 24px;
}
.api-docs li {
    margin-bottom: 14px;
}
.api-docs code {
    font-size: 0.85em;
    overflow-wrap: anywhere;
}
.endpoint {
    display: block;
    background: #eadcc6;
    padding: 12px 18px;
    margin-bottom: 22px;
    font-size: 17px !important;
}
.table-scroll {
    overflow-x: auto;
}
.api-docs table {
    width: 100%;
    min-width: 500px;
    border-collapse: collapse;
    font-size: 16px;
    margin: 15px 0 25px;
}
.api-docs th,
.api-docs td {
    text-align: left;
    vertical-align: top;
    padding: 15px;
    border-bottom: 1px solid #b8a78f;
}
.api-docs th {
    background: #eadcc6;
}
.api-docs td:first-child {
    font-family: monospace;
    width: 180px;
}
.api-docs footer {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    padding: 24px 6vw;
    background: #27231f;
    color: #fff4e4;
    font-size: 14px;
}
@media (max-width: 800px) {
    .docs-layout {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    .docs-layout aside {
        position: static;
    }
    .docs-layout aside nav {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 12px 20px;
    }
    .api-docs header {
        flex-wrap: wrap;
    }
    .api-docs header nav {
        font-size: 15px;
        gap: 20px;
    }
    .api-docs main {
        padding: 35px 6vw;
    }
    .lead {
        font-size: 19px;
    }
    .api-docs h1 {
        font-size: 46px;
    }
    .release-note {
        margin-bottom: 30px;
        padding: 16px;
    }
    .api-docs h2 {
        font-size: 28px;
    }
}
</style>
