<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Plus, Search, CalendarDays, Pencil, Trash2, X, ExternalLink } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Table from '@/Components/Table.vue';
import Handler from './Handler.vue';
const props = defineProps({
        events: Array,
        eventTemplates: Array,
        fieldTemplates: Array,
        openCreate: Boolean,
    }),
    search = ref(''),
    sourceFilter = ref(''),
    templateFilter = ref(''),
    showEventModal = ref(props.openCreate),
    selected = ref(null),
    deleting = ref(null),
    deletion = useForm({});
const filtered = computed(() =>
        props.events.filter(
            (e) =>
                (!sourceFilter.value || e.source === sourceFilter.value) &&
                (!templateFilter.value || e.template_id === Number(templateFilter.value)) &&
                `${e.title || ''} ${Object.values(e.event_data || {})
                    .filter((v) => typeof v !== 'object')
                    .join(' ')}`
                    .toLowerCase()
                    .includes(search.value.toLowerCase()),
        ),
    ),
    groups = computed(() =>
        props.eventTemplates
            .map((template) => ({
                template,
                events: filtered.value.filter((e) => e.template_id === template.id),
            }))
            .filter((g) => g.events.length),
    );
const columns = (t) => [
    { field: '_title', header: 'Event' },
    ...(t.fields || [])
        .map((id) => props.fieldTemplates.find((f) => f.id === id))
        .filter((f) => f && !['title', 'name', 'humanitix_title'].includes(f.field_id))
        .map((f) => ({
            field: f.field_id,
            header: f.label,
            format: ['date', 'datetime'].includes(f.type) ? f.type : 'text',
        })),
    { field: '_source', header: 'Source' },
];
const open = (event = null) => {
        selected.value = event;
        showEventModal.value = true;
    },
    remove = () =>
        deletion.delete(route('events.destroy', deleting.value.id), {
            onSuccess: () => (deleting.value = null),
        });
</script>
<template>
    <AppLayout title="Events"
        ><div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="workspace-eyebrow mb-2">Your event collection</p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Events <span class="ml-2 text-base font-normal text-slate-400">{{ events.length }}</span>
                </h1>
                <p class="mt-3 text-sm text-slate-500">
                    Everything your team is bringing to life, in one place.
                </p>
            </div>
            <button class="workspace-primary" @click="open()"><Plus class="size-4" />Create event</button>
        </div>
        <section class="workspace-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:justify-between">
                <div class="relative w-full sm:max-w-sm">
                    <Search class="absolute left-3 top-3 size-4 text-slate-400" /><label
                        for="event-search"
                        class="sr-only"
                        >Search events</label
                    ><input
                        id="event-search"
                        v-model="search"
                        type="search"
                        placeholder="Search your events…"
                        class="workspace-input pl-10"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <label for="source-filter" class="sr-only">Filter by source</label
                    ><select id="source-filter" v-model="sourceFilter" class="workspace-input sm:w-40">
                        <option value="">All sources</option>
                        <option value="eventnest">EventNest</option>
                        <option value="humanitix">Humanitix</option></select
                    ><label for="template-filter" class="sr-only">Filter by template</label
                    ><select id="template-filter" v-model="templateFilter" class="workspace-input sm:w-48">
                        <option value="">All templates</option>
                        <option v-for="t in eventTemplates" :key="t.id" :value="t.id">
                            {{ t.name }}
                        </option></select
                    ><button
                        v-if="search || templateFilter || sourceFilter"
                        aria-label="Clear filters"
                        class="p-2"
                        @click="
                            search = '';
                            templateFilter = '';
                            sourceFilter = '';
                        "
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </div>
            <div v-if="!filtered.length" class="px-6 py-20 text-center">
                <CalendarDays class="mx-auto mb-5 size-10 text-emerald-700" />
                <h2 class="text-lg font-semibold">
                    {{ events.length ? 'No events match your search' : 'Make room for something great' }}
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500">
                    {{
                        events.length
                            ? 'Try another search or template.'
                            : 'Start with custom fields and a template, then create your first event.'
                    }}
                </p>
                <Link
                    v-if="!eventTemplates.length"
                    :href="route('event-templates.index')"
                    class="workspace-primary mt-6"
                    >Set up event templates</Link
                >
            </div>
            <section
                v-for="group in groups"
                :key="group.template.id"
                class="border-b border-slate-200 last:border-0"
            >
                <h2 class="bg-slate-50 px-6 py-4 text-sm font-semibold">
                    {{ group.template.name }}
                    <span class="ml-2 text-xs font-normal text-slate-400">{{ group.events.length }}</span>
                </h2>
                <Table
                    :data="
                        group.events.map((e) => ({
                            ...e.event_data,
                            id: e.id,
                            _title: e.title || e.event_data?.title || e.event_data?.name || `Event #${e.id}`,
                            _source: e.source === 'humanitix' ? 'Humanitix' : 'EventNest',
                            _url: e.source_url,
                        }))
                    "
                    :columns="columns(group.template)"
                    paginate
                    ><template #actions="{ row }"
                        ><div class="flex justify-end gap-1">
                            <a
                                v-if="group.template.source === 'humanitix' && row._url"
                                :href="row._url"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Open event in Humanitix"
                                class="p-2 text-emerald-700"
                                ><ExternalLink class="size-4" /></a
                            ><button
                                v-if="group.template.source !== 'humanitix'"
                                :aria-label="`Edit event ${row.id}`"
                                class="p-2 text-slate-500 hover:text-emerald-700"
                                @click="open(events.find((e) => e.id === row.id))"
                            >
                                <Pencil class="size-4" /></button
                            ><button
                                :aria-label="`Delete event ${row.id}`"
                                class="p-2 text-slate-500 hover:text-red-700"
                                @click="deleting = events.find((e) => e.id === row.id)"
                            >
                                <Trash2 class="size-4" />
                            </button></div></template
                ></Table>
            </section>
        </section>
        <Modal :show="showEventModal" max-width="3xl" @close="showEventModal = false"
            ><Handler
                v-if="showEventModal"
                :key="selected?.id || 'new'"
                :event="selected"
                :templates="eventTemplates.filter((t) => t.source !== 'humanitix')"
                :fields="fieldTemplates"
                @submit="showEventModal = false"
                @cancel="showEventModal = false" /></Modal
        ><ConfirmationModal :show="!!deleting" @close="deleting = null"
            ><template #title>Delete event?</template
            ><template #content
                >This event will be removed from EventNest and API results. Imported events remain at their
                source and stay hidden on future syncs.</template
            ><template #footer
                ><button class="workspace-secondary" @click="deleting = null">Cancel</button
                ><button class="workspace-primary ml-3" :disabled="deletion.processing" @click="remove">
                    Delete event
                </button></template
            ></ConfirmationModal
        ></AppLayout
    >
</template>
