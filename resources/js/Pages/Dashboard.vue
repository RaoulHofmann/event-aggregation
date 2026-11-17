<script setup>
import { Link } from "@inertiajs/vue3";
import {
    CalendarDays,
    Layers,
    SlidersHorizontal,
    Plus,
    ArrowRight,
    ArrowUpRight,
    KeyRound,
} from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
defineProps({ stats: Object, recentEvents: Array });
const metrics = [
    [
        "events",
        "Total events",
        CalendarDays,
        "events.index",
        "Events in this workspace",
    ],
    [
        "templates",
        "Event templates",
        Layers,
        "event-templates.index",
        "Structures for your events",
    ],
    [
        "fields",
        "Custom fields",
        SlidersHorizontal,
        "field-templates.index",
        "Capture the details that matter",
    ],
];
</script>
<template>
    <AppLayout title="Overview"
        ><section class="mb-9 flex flex-wrap items-start justify-between gap-5">
            <div>
                <p class="workspace-eyebrow mb-3">
                    Your workspace, at a glance
                </p>
                <h1
                    class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl"
                >
                    A home for all your events.
                </h1>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Create, organise and share your events from one place.
                </p>
            </div>
            <Link
                :href="route('events.index', { create: 1 })"
                class="workspace-primary"
                ><Plus class="size-4" />Create event</Link
            >
        </section>
        <section
            aria-label="Workspace totals"
            class="grid gap-5 md:grid-cols-3"
        >
            <Link
                v-for="m in metrics"
                :key="m[0]"
                :href="route(m[3])"
                class="workspace-panel p-6 hover:border-[#d3bda8]"
                ><div class="mb-5 flex items-center justify-between">
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-[#fcf3e7] text-[#a44732]"
                        ><component :is="m[2]" class="size-5" /></span
                    ><ArrowUpRight class="size-4 text-slate-500" />
                </div>
                <p class="text-sm text-slate-500">{{ m[1] }}</p>
                <p class="mt-2 text-4xl font-semibold text-slate-950">
                    {{ stats[m[0]] }}
                </p>
                <p class="mt-3 text-xs text-slate-500">{{ m[4] }}</p></Link
            >
        </section>
        <div class="mt-8 grid items-start gap-6 xl:grid-cols-[1.7fr_1fr]">
            <section class="workspace-panel overflow-hidden">
                <div
                    class="flex items-center justify-between border-b border-slate-100 p-6"
                >
                    <div>
                        <h2 class="font-semibold">Recent events</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            The latest additions to your workspace
                        </p>
                    </div>
                    <Link
                        :href="route('events.index')"
                        class="text-xs font-semibold text-[#a44732]"
                        >View all →</Link
                    >
                </div>
                <div v-if="!recentEvents.length" class="px-6 py-14 text-center">
                    <CalendarDays class="mx-auto mb-4 size-9 text-slate-300" />
                    <h3 class="text-sm font-semibold">
                        Your next event starts here
                    </h3>
                    <p
                        class="mx-auto mt-2 max-w-xs text-sm leading-6 text-slate-500"
                    >
                        Add a title, date and place. Use templates and custom
                        fields when you need more detail.
                    </p>
                    <Link
                        :href="route('events.index', { create: 1 })"
                        class="workspace-secondary mt-5"
                        >Create your first event<ArrowRight class="size-4"
                    /></Link>
                </div>
                <Link
                    v-for="event in recentEvents"
                    :key="event.id"
                    :href="route('events.index')"
                    class="flex items-center gap-4 border-b border-slate-100 px-6 py-5 last:border-0 hover:bg-slate-50"
                    ><CalendarDays class="size-5 text-slate-500" /><span
                        class="min-w-0 flex-1"
                        ><span class="block truncate text-sm font-semibold">{{
                            event.title ||
                            event.event_data?.title ||
                            event.event_data?.name ||
                            `Event #${event.id}`
                        }}</span
                        ><span class="mt-1 block text-xs text-slate-500">{{
                            event.template?.name
                        }}</span></span
                    ><span class="text-xs text-slate-500">{{
                        new Date(event.created_at).toLocaleDateString()
                    }}</span></Link
                >
            </section>
            <section class="space-y-5">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 text-slate-900"
                >
                    <KeyRound class="mb-5 size-6 text-[#a44732]" />
                    <h2 class="text-lg font-semibold">Your events. One API.</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-500">
                        Bring your event data into your website or application
                        with a single EventNest API.
                    </p>
                    <a
                        href="/docs/api"
                        class="mt-4 block text-sm font-medium text-[#a44732] underline underline-offset-4"
                        >Read API documentation</a
                    >
                    <Link
                        :href="route('api-tokens.index')"
                        class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-[#a44732]"
                        >Manage API access<ArrowUpRight class="size-4"
                    /></Link>
                </div>
                <div class="workspace-panel p-6">
                    <h2 class="text-sm font-semibold">Make it yours</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Build templates around your events, with the fields your
                        team needs.
                    </p>
                    <Link
                        :href="route('event-templates.index')"
                        class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-[#a44732]"
                        >Manage templates<ArrowRight class="size-4"
                    /></Link>
                </div>
            </section>
        </div>
        <p
            class="mt-8 border-t border-slate-200 pt-4 text-sm leading-6 text-slate-500"
        >
            EventNest is a work in progress. Access and usage are currently
            free. Future pricing is to be determined.
        </p></AppLayout
    >
</template>
