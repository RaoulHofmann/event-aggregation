<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
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
const page = usePage();
const supportForm = useForm({ subject: "", message: "" });
const sendSupport = () => supportForm.post(route("support-tickets.store"), {
    preserveScroll: true,
    onSuccess: () => supportForm.reset(),
});
const metrics = [
    {
        key: "events",
        title: "Events",
        path: "events.index",
        icon: CalendarDays,
        tone: "sand",
    },
    {
        key: "templates",
        title: "Event templates",
        path: "event-templates.index",
        icon: Layers,
        tone: "lilac",
    },
    {
        key: "fields",
        title: "Custom fields",
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
                    <h1>{{ page.props.auth.user.current_team?.name || "Overview" }}</h1>
                    <div class="welcome-actions">
                        <Link
                            :href="route('events.index', { create: 1 })"
                            class="workspace-primary"
                            ><Plus class="size-4" />Create event</Link
                        ><Link
                            :href="route('integrations.index')"
                            class="connect-link"
                            ><PlugZap class="size-4" />Integrations</Link
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
                </Link>
            </section>
            <div class="dashboard-content">
                <section class="recent-events">
                    <header>
                        <h2>Recent events</h2>
                        <Link :href="route('events.index')"
                            >View all <ArrowUpRight class="size-4"
                        /></Link>
                    </header>
                    <div v-if="!recentEvents.length" class="first-event">
                        <div class="empty-calendar">
                            <CalendarDays class="size-8" />
                        </div>
                        <h3>No events yet</h3>
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
                                    : "EventJunction"
                            }}<small>{{ event.status }}</small></span
                        ><ArrowUpRight class="size-4 shrink-0 text-slate-400"
                    /></Link>
                </section>
                <nav class="api-note" aria-label="API resources">
                    <span><Code2 class="size-5" /> API</span>
                    <div>
                        <Link :href="route('api-tokens.index')">API access <ArrowUpRight class="size-4" /></Link>
                        <a href="/docs/api">API docs <ArrowUpRight class="size-4" /></a>
                    </div>
                </nav>
                <details class="workspace-panel min-w-0 p-5">
                    <summary class="cursor-pointer font-semibold">Contact support</summary>
                    <form class="mt-4 max-w-2xl space-y-4" @submit.prevent="sendSupport">
                        <div>
                            <label for="support-subject" class="workspace-label">Subject</label>
                            <input id="support-subject" v-model="supportForm.subject" class="workspace-input" maxlength="160" required />
                            <p v-if="supportForm.errors.subject" class="mt-1 text-sm text-red-700" role="alert">{{ supportForm.errors.subject }}</p>
                        </div>
                        <div>
                            <label for="support-message" class="workspace-label">Message</label>
                            <textarea id="support-message" v-model="supportForm.message" class="workspace-input" rows="4" maxlength="5000" required />
                            <p v-if="supportForm.errors.message" class="mt-1 text-sm text-red-700" role="alert">{{ supportForm.errors.message }}</p>
                        </div>
                        <button class="workspace-primary" :disabled="supportForm.processing">Submit ticket</button>
                    </form>
                </details>
            </div>
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
.dashboard-welcome h1 {
    font-size: clamp(31px, 3vw, 41px);
    font-weight: 750;
    line-height: 1.07;
    letter-spacing: -1.5px;
    overflow-wrap: anywhere;
    margin: 0 0 18px;
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
.dashboard-content {
    display: grid;
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
.api-note,
.api-note > span,
.api-note > div,
.api-note a {
    display: flex;
    align-items: center;
    gap: 10px;
}
.api-note {
    justify-content: space-between;
    flex-wrap: wrap;
    padding: 13px 20px;
    border: 1px solid #e4e0d6;
    border-radius: 12px;
    background: #f9f7f1;
    color: #514438;
    font-size: 14px;
    font-weight: 600;
}
.api-note > div {
    flex-wrap: wrap;
    gap: 18px;
}
.api-note a {
    gap: 5px;
    font-size: 13px;
    text-decoration: underline;
    text-underline-offset: 3px;
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
    .welcome-actions {
        gap: 17px;
    }
    .connect-link {
        font-size: 12px;
    }
}
</style>
