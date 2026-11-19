<script setup>
import { computed, ref, watch, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Modal.vue";
import Handler from "./Handler.vue";
const props = defineProps({
    events: Object,
    eventTemplates: Array,
    fieldTemplates: Array,
    openCreate: Boolean,
    filters: Object,
    undatedCount: Number,
});
const view = ref(window.innerWidth < 768 ? "agenda" : "month"),
    anchor = ref(new Date()),
    displayZone = ref(
        Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC",
    );
const zones = [
    ...new Set([
        displayZone.value,
        "UTC",
        "Australia/Perth",
        "Australia/Sydney",
        "Europe/London",
        "America/New_York",
        ...(Intl.supportedValuesOf?.("timeZone") || []),
    ]),
].sort();
const search = ref(props.filters.search || ""),
    source = ref(props.filters.source || ""),
    template = ref(props.filters.template || ""),
    status = ref(props.filters.status || "");
const occurrences = ref([]),
    loading = ref(false),
    error = ref(""),
    selected = ref(null),
    showForm = ref(props.openCreate),
    details = ref(null),
    initialDate = ref("");
const action = useForm({}),
    occurrenceForm = useForm({
        occurrence_date: "",
        cancelled: false,
        starts_at: "",
        ends_at: "",
    });
const pad = (n) => String(n).padStart(2, "0"),
    key = (d) =>
        `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const first = computed(
    () => new Date(anchor.value.getFullYear(), anchor.value.getMonth(), 1),
);
const gridStart = computed(() => {
    const d = new Date(view.value === "week" ? anchor.value : first.value);
    d.setDate(d.getDate() - ((d.getDay() + 6) % 7));
    return d;
});
const days = computed(() =>
    Array.from({ length: view.value === "week" ? 7 : 42 }, (_, i) => {
        const d = new Date(gridStart.value);
        d.setDate(d.getDate() + i);
        return d;
    }),
);
const label = computed(() =>
    anchor.value.toLocaleDateString(undefined, {
        month: "long",
        year: "numeric",
    }),
);
const sourceNames = {
    eventnest: "EventJunction",
    humanitix: "Humanitix",
    google_calendar: "Google Calendar",
    ics: "Calendar feed",
    csv: "CSV import",
    wordpress: "WordPress",
    eventbrite: "Eventbrite",
};
const sourceName = (source) => sourceNames[source] || source;
const matches = (e) =>
    (!source.value || e.source === source.value) &&
    (!template.value || e.template_id === Number(template.value)) &&
    (!status.value || e.status === status.value) &&
    (e.title || "").toLowerCase().includes(search.value.toLowerCase());
const filtered = computed(() => occurrences.value.filter(matches));
const dayKey = (iso) =>
    new Intl.DateTimeFormat("sv-SE", {
        timeZone: displayZone.value,
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    }).format(new Date(iso));
const onDay = (d) =>
    filtered.value.filter((e) => {
        const date = key(d),
            start = e.all_day ? e.start_date : dayKey(e.starts_at);
        const end = e.all_day
            ? e.end_date
            : dayKey(
                  new Date(
                      Math.max(
                          new Date(e.starts_at).getTime(),
                          new Date(e.ends_at).getTime() - 1,
                      ),
                  ).toISOString(),
              );
        return (
            date >= start &&
            (e.all_day && end > start ? date < end : date <= end)
        );
    });
const agendaDays = computed(() =>
    days.value
        .filter((d) => d.getMonth() === anchor.value.getMonth())
        .map((date) => ({ date, items: onDay(date) }))
        .filter((g) => g.items.length),
);
let requestId = 0;
const load = async () => {
    const id = ++requestId;
    loading.value = true;
    error.value = "";
    const end = new Date(gridStart.value);
    end.setDate(end.getDate() + (view.value === "week" ? 7 : 42));
    try {
        const { data } = await axios.get(route("api.v1.occurrences"), {
            params: {
                from: new Date(
                    gridStart.value.getTime() - 172800000,
                ).toISOString(),
                to: new Date(end.getTime() + 172800000).toISOString(),
                search: search.value,
                source: source.value,
                template: template.value,
                status: status.value,
            },
        });
        if (id === requestId) occurrences.value = data.data;
    } catch {
        if (id === requestId) {
            occurrences.value = [];
            error.value =
                "Unable to load this calendar. Try again or choose the table view.";
        }
    } finally {
        if (id === requestId) loading.value = false;
    }
};
const move = (n) => {
    if (view.value === "week") {
        const d = new Date(anchor.value);
        d.setDate(d.getDate() + n * 7);
        anchor.value = d;
    } else
        anchor.value = new Date(
            anchor.value.getFullYear(),
            anchor.value.getMonth() + n,
            1,
        );
};
const tableFilters = () => {
    router.get(
        route("events.index"),
        {
            search: search.value,
            source: source.value,
            template: template.value,
            status: status.value,
            undated: props.filters.undated || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
const setUndated = (undated) =>
    router.get(
        route("events.index"),
        {
            search: search.value,
            source: source.value,
            template: template.value,
            status: status.value,
            undated: undated ? 1 : undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
let timer;
watch([search, source, template, status], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        tableFilters();
        load();
    }, 300);
});
watch([anchor, view], load);
onMounted(load);
const open = (event = null, date = "") => {
    selected.value = event;
    initialDate.value = date;
    showForm.value = true;
    details.value = null;
};
const finished = () => {
    showForm.value = false;
    load();
};
const time = (e) =>
    e.all_day
        ? "All day"
        : new Date(e.starts_at).toLocaleTimeString(undefined, {
              hour: "numeric",
              minute: "2-digit",
              timeZone: displayZone.value,
          });
const showDetails = (e) => {
    details.value = e;
    occurrenceForm.reset();
    occurrenceForm.clearErrors();
    occurrenceForm.occurrence_date = e.occurrence_date;
};
const editSeries = async (duplicate = false) => {
    try {
        const { data } = await axios.get(
            route("events.show", details.value.event_id),
        );
        open(
            duplicate
                ? {
                      ...data,
                      id: null,
                      title: `${data.title || "Event"} (copy)`,
                  }
                : data,
        );
    } catch {
        error.value = "Could not load this event.";
    }
};
const saveOccurrence = (cancelled) => {
    occurrenceForm.cancelled = cancelled;
    occurrenceForm.post(route("events.exceptions", details.value.event_id), {
        preserveScroll: true,
        onSuccess: () => {
            details.value = null;
            load();
        },
    });
};
const remove = (event) => {
    if (
        window.confirm(
            event.recurrence
                ? "Delete this entire series?"
                : "Remove this event from EventJunction? Imported events remain hidden after future syncs.",
        )
    )
        action.delete(route("events.destroy", event.id), {
            preserveScroll: true,
            onSuccess: load,
        });
};
</script>
<template>
    <AppLayout title="Events" wide>
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <h1 class="text-3xl font-semibold">Events</h1>
            <button class="workspace-primary" @click="open()">
                Create event
            </button>
        </div>
        <section class="workspace-panel overflow-hidden">
            <div class="flex flex-wrap gap-3 border-b p-4">
                <input
                    v-model="search"
                    aria-label="Search events"
                    type="search"
                    placeholder="Search events…"
                    class="workspace-input sm:max-w-xs"
                />
                <select
                    v-model="source"
                    aria-label="Filter by source"
                    class="workspace-input sm:w-40"
                >
                    <option value="">All sources</option>
                    <option v-for="(name, key) in sourceNames" :key="key" :value="key">{{ name }}</option>
                </select>
                <select
                    v-model="template"
                    aria-label="Filter by template"
                    class="workspace-input sm:w-44"
                >
                    <option value="">All templates</option>
                    <option
                        v-for="t in eventTemplates"
                        :key="t.id"
                        :value="t.id"
                    >
                        {{ t.name }}
                    </option>
                </select>
                <select
                    v-model="status"
                    aria-label="Filter by status"
                    class="workspace-input sm:w-40"
                >
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button
                    v-if="search || source || template || status"
                    class="workspace-secondary"
                    aria-label="Clear filters"
                    @click="
                        search = '';
                        source = '';
                        template = '';
                        status = '';
                    "
                >
                    Clear filters
                </button>
            </div>
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b p-4"
            >
                <div class="flex items-center gap-2">
                    <button
                        class="workspace-secondary"
                        :aria-label="
                            view === 'week' ? 'Previous week' : 'Previous month'
                        "
                        @click="move(-1)"
                    >
                        ‹</button
                    ><button
                        class="workspace-secondary"
                        @click="anchor = new Date()"
                    >
                        Today</button
                    ><button
                        class="workspace-secondary"
                        :aria-label="
                            view === 'week' ? 'Next week' : 'Next month'
                        "
                        @click="move(1)"
                    >
                        ›
                    </button>
                    <h2 class="ml-2 font-semibold">{{ label }}</h2>
                    <input
                        aria-label="Jump to date"
                        type="date"
                        class="workspace-input w-36"
                        :value="key(anchor)"
                        @change="
                            anchor = new Date($event.target.value + 'T12:00:00')
                        "
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="v in ['month', 'week', 'agenda', 'table']"
                        :key="v"
                        :aria-pressed="view === v"
                        :class="
                            view === v
                                ? 'workspace-primary'
                                : 'workspace-secondary'
                        "
                        @click="view = v"
                    >
                        {{ v[0].toUpperCase() + v.slice(1) }}</button
                    ><button
                        class="workspace-secondary"
                        @click="
                            view = 'table';
                            setUndated(true);
                        "
                    >
                        Undated ({{ undatedCount }})
                    </button>
                </div>
            </div>
            <div
                class="flex flex-wrap items-center gap-3 px-4 py-2 text-xs text-slate-500"
            >
                <label
                    >Display timezone
                    <select
                        v-model="displayZone"
                        class="ml-1 max-w-48 rounded border-slate-200 text-xs"
                    >
                        <option v-for="zone in zones" :key="zone" :value="zone">
                            {{ zone }}
                        </option>
                    </select></label
                ><span>All-day events keep their original dates.</span>
            </div>
            <p v-if="loading && view !== 'table'" role="status" class="p-4">
                Loading calendar…
            </p>
            <p v-if="error" role="alert" class="p-4 text-red-700">
                {{ error }}
                <button class="underline" @click="load">Retry</button>
            </p>
            <template v-if="view === 'month' || view === 'week'">
                <div class="overflow-x-auto">
                    <div class="min-w-[700px]">
                        <div
                            class="grid grid-cols-7 bg-slate-50 text-center text-xs font-semibold"
                        >
                            <div
                                v-for="d in [
                                    'Mon',
                                    'Tue',
                                    'Wed',
                                    'Thu',
                                    'Fri',
                                    'Sat',
                                    'Sun',
                                ]"
                                :key="d"
                                class="p-3"
                            >
                                {{ d }}
                            </div>
                        </div>
                        <div class="grid grid-cols-7">
                            <section
                                v-for="d in days"
                                :key="key(d)"
                                class="border-b border-r p-2 sm:p-3"
                                :style="{
                                    minHeight:
                                        view === 'week'
                                            ? 'max(420px, calc(100dvh - 350px))'
                                            : '148px',
                                }"
                                :class="
                                    d.getMonth() !== anchor.getMonth()
                                        ? 'bg-slate-50 text-slate-400'
                                        : ''
                                "
                            >
                                <button
                                    :aria-label="`Create event on ${key(d)}`"
                                    class="mb-2 rounded-full px-2 py-1 text-sm hover:bg-emerald-100"
                                    :class="
                                        key(d) === key(new Date())
                                            ? 'bg-emerald-700 text-white'
                                            : ''
                                    "
                                    @click="open(null, key(d))"
                                >
                                    {{ d.getDate() }}
                                </button>
                                <button
                                    v-for="e in onDay(d).slice(0, 3)"
                                    :key="e.id"
                                    class="mb-1 block w-full rounded border-l-2 border-emerald-600 bg-emerald-50 px-2 py-1 text-left text-xs text-emerald-950 hover:bg-emerald-100"
                                    :class="
                                        e.status === 'cancelled'
                                            ? 'line-through opacity-60'
                                            : ''
                                    "
                                    @click="showDetails(e)"
                                >
                                    <span class="font-medium"
                                        >{{ time(e) }} {{ e.title }}</span
                                    ><span class="block text-[10px]"
                                        >{{ sourceName(e.source) }} · {{ e.status
                                        }}{{
                                            e.recurring ? " · Repeats" : ""
                                        }}</span
                                    ></button
                                ><button
                                    v-if="onDay(d).length > 3"
                                    class="text-xs font-medium text-emerald-800 underline"
                                    @click="
                                        anchor = new Date(d);
                                        view = 'agenda';
                                    "
                                >
                                    +{{ onDay(d).length - 3 }} more · open
                                    agenda
                                </button>
                            </section>
                        </div>
                    </div>
                </div>
            </template>
            <div v-if="view === 'agenda'" class="divide-y">
                <p
                    v-if="!agendaDays.length && !loading"
                    class="p-10 text-center text-slate-500"
                >
                    No scheduled events this month match your filters.
                </p>
                <section v-for="g in agendaDays" :key="key(g.date)" class="p-5">
                    <h3 class="mb-3 font-semibold">
                        {{
                            g.date.toLocaleDateString(undefined, {
                                weekday: "long",
                                day: "numeric",
                                month: "short",
                            })
                        }}
                    </h3>
                    <button
                        v-for="e in g.items"
                        :key="e.id"
                        class="mb-2 flex w-full flex-wrap gap-3 rounded-lg border p-3 text-left hover:bg-slate-50"
                        @click="showDetails(e)"
                    >
                        <span class="text-sm text-emerald-800">{{
                            time(e)
                        }}</span
                        ><span class="flex-1 font-medium">{{ e.title }}</span
                        ><span class="text-xs text-slate-500"
                            >{{ sourceName(e.source) }} · {{ e.status
                            }}{{ e.recurring ? " · Repeats" : "" }}</span
                        >
                    </button>
                </section>
            </div>
            <div v-if="view === 'table'" class="overflow-x-auto">
                <button
                    v-if="filters.undated"
                    class="m-4 workspace-secondary"
                    @click="setUndated(false)"
                >
                    Show dated and undated events
                </button>
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="p-4">Event</th>
                            <th class="p-4">Schedule</th>
                            <th class="p-4">Source / status</th>
                            <th class="p-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="e in events.data"
                            :key="e.id"
                            class="border-t"
                        >
                            <td class="p-4 font-medium">
                                {{
                                    e.title ||
                                    e.event_data?.title ||
                                    e.event_data?.name ||
                                    `Event #${e.id}`
                                }}
                                <p class="text-xs font-normal text-slate-500">
                                    {{ e.location }}
                                </p>
                            </td>
                            <td class="p-4">
                                {{
                                    e.starts_at
                                        ? new Date(e.starts_at).toLocaleString()
                                        : "Undated"
                                }}<span
                                    v-if="e.recurrence"
                                    class="block text-xs"
                                    >Repeats {{ e.recurrence.frequency }}</span
                                >
                            </td>
                            <td class="p-4">{{ sourceName(e.source) }} · {{ e.status }}</td>
                            <td class="p-4">
                                <button
                                    :aria-label="`Edit event ${e.id}`"
                                    class="mr-3 text-emerald-800 underline"
                                    @click="open(e)"
                                >
                                    Edit</button
                                ><a
                                    v-if="e.source_url"
                                    :href="e.source_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mr-3 underline"
                                    >Open source</a
                                ><button
                                    :aria-label="`Delete event ${e.id}`"
                                    class="text-red-700 underline"
                                    @click="remove(e)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!events.data.length" class="p-10 text-center">
                    No events match your search
                </p>
                <nav aria-label="Event pages" class="flex flex-wrap gap-2 p-4">
                    <button
                        v-for="(link, i) in events.links"
                        :key="i"
                        :disabled="!link.url || link.active"
                        class="workspace-secondary"
                        @click="
                            router.visit(link.url, {
                                preserveState: true,
                                preserveScroll: true,
                            })
                        "
                    >
                        <span v-html="link.label" />
                    </button>
                </nav>
            </div>
        </section>
        <Modal :show="showForm" max-width="editor" @close="showForm = false"
            ><Handler
                v-if="showForm"
                :event="selected"
                :initial-date="initialDate"
                :templates="
                    eventTemplates.filter((t) => t.source === 'eventnest')
                "
                :fields="fieldTemplates"
                @submit="finished"
                @cancel="showForm = false"
        /></Modal>
        <Modal :show="!!details" max-width="2xl" @close="details = null"
            ><div v-if="details" class="space-y-5 bg-white p-6">
                <div class="flex justify-between gap-3">
                    <h2 class="text-xl font-semibold">{{ details.title }}</h2>
                    <button
                        aria-label="Close event details"
                        @click="details = null"
                    >
                        Close
                    </button>
                </div>
                <p>
                    {{
                        details.all_day
                            ? `${details.start_date} → ${details.end_date} (exclusive), all day`
                            : new Date(details.starts_at).toLocaleString()
                    }}<span class="block text-xs text-slate-500"
                        >Event timezone: {{ details.timezone }} ·
                        {{ details.status }} · {{ sourceName(details.source) }}</span
                    >
                </p>
                <p v-if="details.location">{{ details.location }}</p>
                <p class="whitespace-pre-wrap text-sm text-slate-600">
                    {{ details.description }}
                </p>
                <button class="workspace-primary" @click="editSeries(false)">
                    {{
                        details.recurring ? "Edit entire series" : "Edit event"
                    }}</button
                ><button
                    class="workspace-secondary ml-2"
                    @click="editSeries(true)"
                >
                    Duplicate
                    {{ details.recurring ? "series" : "event" }}</button
                ><a
                    v-if="details.source_url"
                    class="workspace-primary"
                    :href="details.source_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    >{{
                        details.source === "google_calendar"
                            ? "View on Google Calendar"
                            : details.source === "humanitix"
                              ? "View on Humanitix"
                              : "View source event"
                    }}</a
                >
                <dl class="grid gap-3 text-sm">
                    <div v-for="(value, k) in details.custom_fields" :key="k">
                        <dt class="font-medium">
                            {{
                                fieldTemplates.find((f) => f.field_id === k)
                                    ?.label || k
                            }}
                        </dt>
                        <dd class="whitespace-pre-wrap text-slate-600">
                            {{ value }}
                        </dd>
                    </div>
                </dl>
                <fieldset
                    v-if="details.recurring"
                    class="space-y-3 border-t pt-4"
                >
                    <legend class="pt-4 font-semibold">
                        Change this occurrence only
                    </legend>
                    <p class="text-xs text-slate-500">
                        Enter local times in {{ details.timezone }}. Original
                        date: {{ details.occurrence_date }}.
                    </p>
                    <label class="workspace-label"
                        >New start<input
                            v-model="occurrenceForm.starts_at"
                            type="datetime-local"
                            class="workspace-input" /></label
                    ><label class="workspace-label"
                        >New end<input
                            v-model="occurrenceForm.ends_at"
                            type="datetime-local"
                            class="workspace-input"
                    /></label>
                    <p
                        v-for="(message, k) in occurrenceForm.errors"
                        :key="k"
                        role="alert"
                        class="text-sm text-red-700"
                    >
                        {{ message }}
                    </p>
                    <button
                        class="workspace-primary"
                        :disabled="occurrenceForm.processing"
                        @click="saveOccurrence(false)"
                    >
                        Reschedule occurrence</button
                    ><button
                        class="workspace-secondary ml-2"
                        :disabled="occurrenceForm.processing"
                        @click="saveOccurrence(true)"
                    >
                        Skip occurrence
                    </button>
                </fieldset>
            </div></Modal
        >
    </AppLayout>
</template>
