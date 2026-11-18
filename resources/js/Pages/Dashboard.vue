<script setup>
import { Link } from "@inertiajs/vue3";
import {
    CalendarDays,
    Layers,
    SlidersHorizontal,
    Plus,
    ArrowUpRight,
    Code2,
    PlugZap,
} from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
defineProps({ stats: Object, recentEvents: Array });
const metrics = [
    {
        key: "events",
        title: "Events",
        note: "Across your workspace",
        path: "events.index",
        icon: CalendarDays,
        tone: "sand",
    },
    {
        key: "templates",
        title: "Event templates",
        note: "Reusable event structures",
        path: "event-templates.index",
        icon: Layers,
        tone: "lilac",
    },
    {
        key: "fields",
        title: "Custom fields",
        note: "The details your team needs",
        path: "field-templates.index",
        icon: SlidersHorizontal,
        tone: "sage",
    },
];
const today = new Date();
const date = (e) =>
    e.starts_at
        ? new Date(e.starts_at).toLocaleDateString(undefined, {
              month: "short",
              day: "numeric",
              ...(e.all_day ? { timeZone: e.timezone || "UTC" } : {}),
          })
        : "Undated";
</script>
<template>
    <AppLayout title="Overview"
        ><div class="programme-dashboard">
            <section class="dashboard-welcome">
                <div>
                    <p class="dashboard-eyebrow">
                        YOUR PROGRAMME / AT A GLANCE
                    </p>
                    <h1>Good things,<br /><span>coming together.</span></h1>
                    <p>
                        Keep your events, connected listings and custom details
                        in one working home.
                    </p>
                    <div class="welcome-actions">
                        <Link
                            :href="route('events.index', { create: 1 })"
                            class="workspace-primary"
                            ><Plus class="size-4" />Create event</Link
                        ><Link
                            :href="route('integrations.index')"
                            class="connect-link"
                            ><PlugZap class="size-4" />Connect an account</Link
                        >
                    </div>
                </div>
                <div class="today-note">
                    <span>{{
                        today.toLocaleDateString(undefined, { weekday: "long" })
                    }}</span
                    ><strong>{{
                        today.toLocaleDateString(undefined, { day: "numeric" })
                    }}</strong
                    ><span>{{
                        today.toLocaleDateString(undefined, {
                            month: "long",
                            year: "numeric",
                        })
                    }}</span
                    ><Link :href="route('events.index')"
                        >Open calendar <ArrowUpRight class="size-4"
                    /></Link>
                </div>
            </section>
            <section aria-label="Workspace totals" class="dashboard-totals">
                <Link
                    v-for="metric in metrics"
                    :key="metric.key"
                    :href="route(metric.path)"
                    class="dashboard-total"
                    :class="metric.tone"
                    ><div class="total-top">
                        <span class="metric-icon"
                            ><component
                                :is="metric.icon"
                                class="size-5" /></span
                        ><ArrowUpRight class="size-4 text-slate-400" />
                    </div>
                    <div class="total-value">
                        <span>{{ stats[metric.key] }}</span>
                        <h2>{{ metric.title }}</h2>
                    </div>
                    <p>{{ metric.note }}</p></Link
                >
            </section>
            <div class="dashboard-content">
                <section class="recent-events">
                    <header>
                        <div>
                            <p class="dashboard-eyebrow">LATEST ADDITIONS</p>
                            <h2>Your programme.</h2>
                        </div>
                        <Link :href="route('events.index')"
                            >View all <ArrowUpRight class="size-4"
                        /></Link>
                    </header>
                    <div v-if="!recentEvents.length" class="first-event">
                        <div class="empty-calendar">
                            <CalendarDays class="size-8" />
                        </div>
                        <h3>What’s going in the diary?</h3>
                        <p>
                            Create your first event or bring in a listing from a
                            connected host. Templates can wait until you need
                            them.
                        </p>
                        <div>
                            <Link
                                :href="route('events.index', { create: 1 })"
                                class="workspace-secondary"
                                >Add an event
                                <ArrowUpRight class="size-4" /></Link
                            ><Link
                                :href="route('integrations.index')"
                                class="text-link"
                                >Explore integrations</Link
                            >
                        </div>
                    </div>
                    <Link
                        v-for="event in recentEvents"
                        :key="event.id"
                        :href="route('events.index')"
                        class="recent-event"
                        ><span class="event-date">{{ date(event) }}</span
                        ><span class="event-details"
                            ><strong>{{
                                event.title ||
                                event.event_data?.title ||
                                event.event_data?.name ||
                                `Event #${event.id}`
                            }}</strong
                            ><span>{{
                                event.location ||
                                event.template?.name ||
                                "Basic event"
                            }}</span></span
                        ><span class="event-meta"
                            >{{
                                event.source !== "eventnest"
                                    ? "Imported"
                                    : "EventNest"
                            }}<small>{{ event.status }}</small></span
                        ><ArrowUpRight class="size-4 shrink-0 text-slate-400"
                    /></Link>
                </section>
                <aside class="dashboard-notes">
                    <section class="api-note">
                        <Code2 class="size-6" />
                        <p class="dashboard-eyebrow">FOR YOUR APPLICATIONS</p>
                        <h2>Your events.<br />One API.</h2>
                        <p>
                            Read the same native and imported events your team
                            manages here.
                        </p>
                        <code>GET /api/v1/events</code>
                        <div>
                            <Link :href="route('api-tokens.index')"
                                >API access
                                <ArrowUpRight class="size-4" /></Link
                            ><a href="/docs/api"
                                >Documentation <ArrowUpRight class="size-4"
                            /></a>
                        </div>
                    </section>
                    <section class="template-note">
                        <Layers class="size-5" />
                        <h2>A place for the details.</h2>
                        <p>
                            Use templates to give native events and imported
                            listings the same structure.
                        </p>
                        <Link :href="route('event-templates.index')"
                            >Manage templates <ArrowUpRight class="size-4"
                        /></Link>
                    </section>
                </aside>
            </div>
            <footer class="workspace-access">
                <span>EARLY ACCESS</span>
                <p>
                    EventNest is a work in progress. Access and usage are
                    currently free.
                    <a href="/pricing">Future pricing is TBD.</a>
                </p>
            </footer>
        </div></AppLayout
    >
</template>
<style scoped>
.programme-dashboard {
    color: #303641;
}
.dashboard-welcome {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 35px;
    border: 1px solid #e4e0d6;
    background: #f9f7f1;
    padding: 30px 34px;
    border-radius: 14px;
    margin-bottom: 24px;
}
.dashboard-eyebrow {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    color: #786c5d;
    margin: 0 0 15px;
}
.dashboard-welcome h1 {
    font-size: clamp(31px, 3vw, 41px);
    font-weight: 750;
    line-height: 1.07;
    letter-spacing: -1.5px;
    margin: 0 0 18px;
}
.dashboard-welcome h1 > span {
    color: #94735b;
}
.dashboard-welcome > div > p:last-of-type {
    font-size: 15px;
    line-height: 1.8;
    color: #687383;
    max-width: 470px;
}
.welcome-actions {
    display: flex;
    align-items: center;
    gap: 22px;
    margin-top: 24px;
    flex-wrap: wrap;
}
.connect-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #8b735d;
}
.today-note {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    flex-direction: column;
    min-width: 170px;
    border-left: 1px solid #e4dfd3;
    padding-left: 32px;
    color: #7b7467;
}
.today-note > span {
    font-size: 12px;
}
.today-note > strong {
    font-size: 54px;
    font-weight: 600;
    line-height: 1.15;
    letter-spacing: -2px;
    color: #4f483d;
    margin: 8px 0;
}
.today-note > a {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 500;
    margin-top: 20px;
    color: #93745b;
}
.dashboard-totals {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}
.dashboard-total {
    background: #fff;
    border: 1px solid #e3e7ed;
    border-top: 3px solid #d8cbb0;
    border-radius: 10px;
    padding: 22px 24px;
    transition: box-shadow 0.2s;
}
.dashboard-total:hover {
    box-shadow: 0 5px 20px #30364108;
}
.dashboard-total.lilac {
    border-top-color: #d5cfdf;
}
.dashboard-total.sage {
    border-top-color: #cbd6cd;
}
.total-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}
.metric-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 9px;
    background: #f7f2e9;
    color: #9c8062;
}
.lilac .metric-icon {
    background: #f3f0f7;
    color: #93869f;
}
.sage .metric-icon {
    background: #eff4ef;
    color: #829584;
}
.total-value {
    display: flex;
    align-items: baseline;
    gap: 13px;
}
.total-value > span {
    font-size: 33px;
    font-weight: 650;
    line-height: 1.2;
    letter-spacing: -1px;
}
.total-value h2 {
    font-size: 14px;
    font-weight: 500;
    color: #596676;
}
.dashboard-total > p {
    font-size: 12px;
    color: #687587;
    margin: 12px 0 0;
}
.dashboard-content {
    display: grid;
    grid-template-columns: minmax(0, 1.65fr) minmax(0, 1fr);
    gap: 24px;
    margin-top: 28px;
    align-items: start;
}
.recent-events {
    background: #fff;
    border: 1px solid #e3e7ed;
    border-radius: 12px;
    min-width: 0;
    overflow: hidden;
}
.recent-events > header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    border-bottom: 1px solid #eef0f3;
    padding: 24px;
}
.recent-events > header .dashboard-eyebrow {
    margin-bottom: 7px;
}
.recent-events h2 {
    font-size: 23px;
    font-weight: 650;
    letter-spacing: -0.5px;
}
.recent-events > header > a {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: #99795f;
}
.first-event {
    padding: 40px 30px 45px;
}
.empty-calendar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 62px;
    height: 62px;
    border: 1px solid #e5dccd;
    background: #faf7f0;
    color: #a68c6b;
    border-radius: 12px;
    rotate: -4deg;
    margin-bottom: 25px;
}
.first-event h3 {
    font-size: 21px;
    font-weight: 600;
    letter-spacing: -0.5px;
    margin-bottom: 15px;
}
.first-event > p {
    font-size: 14px;
    line-height: 1.9;
    color: #667385;
    max-width: 390px;
}
.first-event > div:last-child {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 22px;
    margin-top: 24px;
}
.text-link {
    font-size: 13px;
    color: #94765d;
}
.recent-event {
    display: flex;
    align-items: center;
    gap: 17px;
    padding: 22px 24px;
    border-bottom: 1px solid #eef0f3;
}
.recent-event:last-child {
    border-bottom: 0;
}
.recent-event:hover {
    background: #fcfbf8;
}
.event-date {
    width: 58px;
    flex-shrink: 0;
    font-size: 14px;
    font-weight: 600;
    color: #947c62;
}
.event-details {
    flex: 1;
    min-width: 0;
}
.event-details > strong {
    display: block;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.5;
    overflow-wrap: anywhere;
}
.event-details > span {
    display: block;
    font-size: 12px;
    color: #6e7b8a;
    margin-top: 6px;
}
.event-meta {
    font-size: 11px;
    text-align: right;
    color: #8d98a5;
}
.event-meta small {
    display: block;
    font-size: 10px;
    text-transform: capitalize;
    margin-top: 6px;
}
.dashboard-notes {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.api-note {
    background: #373936;
    color: #f3f1e9;
    padding: 27px;
    border-radius: 12px;
}
.api-note > svg {
    color: #cabb9a;
    margin-bottom: 22px;
}
.api-note .dashboard-eyebrow {
    color: #cabb9a;
}
.api-note h2 {
    font-size: 26px;
    font-weight: 650;
    line-height: 1.15;
    letter-spacing: -0.6px;
    margin-bottom: 17px;
}
.api-note > p:not(.dashboard-eyebrow) {
    font-size: 14px;
    line-height: 1.9;
    color: #bbbeb6;
}
.api-note > code {
    display: block;
    border-top: 1px solid #585b53;
    border-bottom: 1px solid #585b53;
    padding: 14px 0;
    font-size: 12px;
    margin: 20px 0;
}
.api-note > div {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.api-note a {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #dfd0af;
}
.template-note {
    border: 1px solid #e1dce8;
    background: #f6f4f8;
    padding: 25px;
    border-radius: 12px;
}
.template-note > svg {
    color: #a096ac;
    margin-bottom: 15px;
}
.template-note h2 {
    font-size: 18px;
    font-weight: 600;
    letter-spacing: -0.3px;
    margin-bottom: 13px;
}
.template-note p {
    font-size: 14px;
    line-height: 1.9;
    color: #70697a;
}
.template-note a {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 500;
    color: #94869b;
    margin-top: 18px;
}
.workspace-access {
    display: flex;
    gap: 20px;
    border-top: 1px solid #e3e7ed;
    padding-top: 18px;
    margin-top: 30px;
    align-items: baseline;
    color: #6e7b8a;
}
.workspace-access > span {
    font-size: 10px;
    letter-spacing: 0.06em;
    white-space: nowrap;
}
.workspace-access > p {
    font-size: 12px;
    line-height: 1.9;
}
.workspace-access a {
    text-decoration: underline;
    text-underline-offset: 3px;
}
@media (max-width: 1200px) {
    .dashboard-content {
        grid-template-columns: 1fr;
    }
    .dashboard-notes {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 700px) {
    .dashboard-welcome {
        padding: 25px;
        align-items: flex-start;
        flex-direction: column;
        gap: 24px;
    }
    .dashboard-welcome h1 {
        font-size: 35px;
    }
    .today-note {
        flex-direction: row;
        min-width: 0;
        width: 100%;
        padding: 18px 0 0;
        border-left: 0;
        border-top: 1px solid #e4dfd3;
        gap: 10px;
        flex-wrap: wrap;
    }
    .today-note > strong {
        font-size: 25px;
        margin: 0;
        order: -1;
    }
    .today-note > span {
        font-size: 11px;
    }
    .today-note > a {
        margin: 0 0 0 auto;
        font-size: 11px;
    }
    .dashboard-totals {
        grid-template-columns: 1fr;
        gap: 14px;
    }
    .dashboard-total {
        display: grid;
        grid-template-columns: 1fr auto;
        padding: 20px;
    }
    .total-top {
        margin: 0;
    }
    .total-top > svg {
        display: none;
    }
    .total-value {
        grid-column: 2;
        grid-row: 1 / span 2;
        align-items: flex-end;
        flex-direction: column;
        gap: 8px;
    }
    .dashboard-total > p {
        margin-top: 10px;
    }
    .dashboard-notes {
        grid-template-columns: 1fr;
    }
    .recent-event {
        padding: 20px 16px;
        gap: 12px;
    }
    .recent-event > svg {
        display: none;
    }
    .first-event {
        padding: 30px 24px;
    }
    .workspace-access {
        flex-direction: column;
        gap: 7px;
    }
    .welcome-actions {
        gap: 17px;
    }
    .connect-link {
        font-size: 12px;
    }
}
</style>
