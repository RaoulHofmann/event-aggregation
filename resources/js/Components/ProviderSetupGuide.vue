<script setup>
import { computed } from "vue";
import { integrationGuides } from "@/Lib/integrationGuides";

const props = defineProps({
    provider: { type: String, required: true },
});
const providerGuide = computed(() => integrationGuides[props.provider]);
const csvExample = "title,starts_at,timezone,external_id\nGarden workshop,2026-12-03T10:00,Australia/Perth,workshop-2026-12";
</script>

<template>
    <details class="w-full min-w-0 rounded-lg border border-slate-200 bg-slate-50/70 text-sm text-slate-700">
        <summary class="cursor-pointer rounded-lg px-3 py-2.5 font-medium text-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700">
            {{ providerGuide.title }}
        </summary>
        <div class="space-y-3 break-words px-3 pb-3 leading-6 [&_a]:font-medium [&_a]:text-emerald-800 [&_a]:underline [&_ol]:list-decimal [&_ol]:space-y-1 [&_ol]:pl-5">
            <template v-if="provider === 'ics'">
                <ol>
                    <li>Open the calendar’s sharing or publishing settings and copy its iCalendar subscription address. Look for <strong>iCal</strong>, <strong>ICS</strong> or <strong>Subscribe</strong>.</li>
                    <li>Choose <strong>Add connection</strong>. Paste the final HTTPS address that serves the calendar file and set the fallback timezone.</li>
                    <li>Choose <strong>Save and queue import</strong>.</li>
                </ol>
                <p>For Outlook, look under <strong>Settings → Calendar → Shared calendars → Publish a calendar</strong>. If publishing is available, choose which details to share and copy the <strong>ICS</strong> link. Check the sharing permissions before publishing.</p>
                <p>Keep unlisted subscription addresses private. EventJunction fetches the feed in the background, so the address must work without a browser sign-in.</p>
            </template>
            <template v-else-if="provider === 'csv'">
                <ol>
                    <li>Put one event on each row. Use <code>title</code> as a column heading and ISO dates such as <code>2026-12-03T10:00</code> for timed events.</li>
                    <li>Export as UTF-8 CSV. Each file can contain up to 500 rows and be up to 1 MB.</li>
                    <li>Select <strong>Choose file</strong>, upload the CSV, then select <strong>Preview CSV</strong>. Once the preview passes, choose <strong>Import drafts</strong>.</li>
                </ol>
                <p>Keep the same <code>external_id</code> for each event if you plan to update it with a later upload.</p>
                <pre class="overflow-x-auto rounded-md border border-slate-200 bg-white p-2 text-xs"><code>{{ csvExample }}</code></pre>
            </template>
            <template v-else-if="provider === 'wordpress'">
                <ol>
                    <li>Check that the WordPress site uses <strong>The Events Calendar</strong> plugin and publishes its events publicly.</li>
                    <li>Add <code>/wp-json/tribe/events/v1/events</code> to the site address and open it in a private browser window. A JSON response with an <code>events</code> list means the feed is accessible.</li>
                    <li>Choose <strong>Add connection</strong>, enter the site's base HTTPS address, such as <code>https://example.org</code>, and choose <strong>Save and queue import</strong>.</li>
                </ol>
                <p>If your WordPress calendar supplies an ICS subscription address, you can also use <strong>Calendar feed</strong>.</p>
            </template>
            <template v-else-if="provider === 'eventbrite'">
                <ol>
                    <li>Sign in to the Eventbrite account that manages the events. Open <strong>Account settings → Developer links → API keys</strong> and copy the <strong>Personal OAuth token</strong> for an API key, or create a key first.</li>
                    <li>Find the numeric organisation ID. Eventbrite’s <a href="https://www.eventbrite.com/platform/api#/reference/organization/list-your-organizations">List your organisations</a> endpoint (<code>GET /v3/users/me/organizations/</code>) returns the ID alongside the organisation name.</li>
                    <li>Choose <strong>Add connection</strong>, enter the organisation ID and token, then choose <strong>Save and queue import</strong>.</li>
                </ol>
                <p>Keep the personal OAuth token private. Use a token from an account with access to the selected organisation.</p>
            </template>
            <template v-else-if="provider === 'humanitix'">
                <ol>
                    <li>Sign in to the Humanitix host account for the events you want. Create or copy an API key using the <a href="https://platform.humanitix.com/docs/api">Humanitix API documentation</a>.</li>
                    <li>Under <strong>Integrations</strong>, choose <strong>Connect Humanitix</strong> and enter the key.</li>
                    <li>Optionally select a template and map its fields, then save the connection to queue an import.</li>
                </ol>
                <p>Keep the API key private and enter it only in the connection form.</p>
            </template>
            <template v-else-if="provider === 'google_calendar'">
                <ol>
                    <li>Under <strong>Integrations</strong>, choose <strong>Connect Google</strong> and sign in with the Google account that can read the calendars you want.</li>
                    <li>Approve read-only access to your calendars and events.</li>
                    <li>Select the calendars and an optional event template, then choose <strong>Save and import</strong>.</li>
                </ol>
            </template>
        </div>
    </details>
</template>
