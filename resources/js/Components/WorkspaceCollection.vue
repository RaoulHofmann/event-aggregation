<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Layers, SlidersHorizontal, Plus, Search, Pencil, Trash2, X } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from './Modal.vue';
import ConfirmationModal from './ConfirmationModal.vue';
const props = defineProps({
    title: String,
    description: String,
    items: Array,
    resource: String,
    singular: String,
    fields: Boolean,
});
const query = ref(''),
    selected = ref(null),
    showForm = ref(false),
    deleting = ref(null),
    available = ref([]),
    loading = ref(false),
    loadError = ref(''),
    columns = ref({});
const form = useForm({
        name: '',
        description: '',
        label: '',
        field_id: '',
        type: 'text',
        required: false,
        options: [],
        fields: [],
        layout: [[], []],
    }),
    deletion = useForm({});
const filtered = computed(() =>
    props.items.filter((i) => (i.name || i.label).toLowerCase().includes(query.value.toLowerCase())),
);
const types = {
    text: 'Text',
    textarea: 'Long text',
    number: 'Whole number',
    decimal: 'Decimal',
    date: 'Date',
    datetime: 'Date and time',
    email: 'Email address',
    url: 'URL',
    select: 'Select options',
    boolean: 'Yes / no',
};
const load = async () => {
    loading.value = true;
    loadError.value = '';
    try {
        available.value = (await axios.get('/api/field-templates')).data;
    } catch {
        loadError.value = 'Could not load fields. Please retry.';
    } finally {
        loading.value = false;
    }
};
const open = (item = null) => {
    selected.value = item;
    form.reset();
    form.clearErrors();
    if (item) Object.assign(form, JSON.parse(JSON.stringify(item)));
    if (!Array.isArray(form.options)) {
        try {
            form.options = JSON.parse(form.options || '[]');
        } catch {
            form.options = [];
        }
    }
    columns.value = Object.fromEntries((form.layout || [[], []]).flatMap((c, i) => c.map((id) => [id, i])));
    showForm.value = true;
    if (!props.fields) load();
};
const submit = () => {
    if (!props.fields)
        form.layout = [0, 1].map((c) => form.fields.filter((id) => Number(columns.value[id] || 0) === c));
    form[selected.value ? 'put' : 'post'](
        route(`${props.resource}.${selected.value ? 'update' : 'store'}`, selected.value?.id),
        { preserveScroll: true, onSuccess: () => (showForm.value = false) },
    );
};
const remove = () =>
    deletion.delete(route(`${props.resource}.destroy`, deleting.value.id), {
        onSuccess: () => (deleting.value = null),
    });
</script>
<template>
    <AppLayout :title="title"
        ><div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="workspace-eyebrow mb-2">Build your event toolkit</p>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950">{{ title }}</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">{{ description }}</p>
            </div>
            <button class="workspace-primary" @click="open()">
                <Plus class="size-4" />Create {{ singular }}
            </button>
        </div>
        <section class="workspace-panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-3 top-3 size-4 text-slate-400" /><label
                        :for="`${resource}-search`"
                        class="sr-only"
                        >Search {{ title.toLowerCase() }}</label
                    ><input
                        :id="`${resource}-search`"
                        v-model="query"
                        type="search"
                        :placeholder="`Search ${title.toLowerCase()}…`"
                        class="workspace-input pl-10"
                    />
                </div>
                <span class="ml-3 text-xs text-slate-500">{{ items.length }} total</span>
            </div>
            <div v-if="!filtered.length" class="px-6 py-16 text-center">
                <component
                    :is="fields ? SlidersHorizontal : Layers"
                    class="mx-auto mb-4 size-10 text-emerald-700"
                />
                <h2 class="font-semibold">
                    {{ query ? 'No matching results' : `Create your first ${singular}` }}
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500">
                    {{
                        fields
                            ? 'Define the details your events need, then reuse them in templates.'
                            : 'Combine custom fields into a reusable structure for your events.'
                    }}
                </p>
                <button v-if="!query" class="workspace-primary mt-6" @click="open()">
                    Create {{ singular }}</button
                ><Link
                    v-if="!fields && !query"
                    :href="route('field-templates.index')"
                    class="mx-auto mt-4 block text-sm text-emerald-700"
                    >Need fields first?</Link
                >
            </div>
            <div
                v-for="item in filtered"
                :key="item.id"
                class="flex items-center gap-4 border-b border-slate-100 px-6 py-5 last:border-0"
            >
                <component
                    :is="fields ? SlidersHorizontal : Layers"
                    class="hidden size-5 shrink-0 text-slate-400 sm:block"
                />
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-sm font-semibold">{{ item.label || item.name }}</h2>
                    <p class="mt-1 truncate text-xs text-slate-500">
                        {{ fields ? item.type : `${item.fields?.length || 0} custom fields`
                        }}<span v-if="item.description"> · {{ item.description }}</span
                        ><span v-if="item.required"> · Required</span>
                    </p>
                </div>
                <span v-if="item.system_field || item.source === 'humanitix'" class="text-xs text-slate-500"
                    >Managed</span
                ><template v-else
                    ><button
                        :aria-label="`Edit ${item.label || item.name}`"
                        class="p-2 text-slate-500 hover:text-emerald-700"
                        @click="open(item)"
                    >
                        <Pencil class="size-4" /></button
                    ><button
                        :aria-label="`Delete ${item.label || item.name}`"
                        class="p-2 text-slate-500 hover:text-red-700"
                        @click="deleting = item"
                    >
                        <Trash2 class="size-4" /></button
                ></template>
            </div>
        </section>
        <Modal :show="showForm" max-width="3xl" @close="showForm = false"
            ><form class="bg-white" @submit.prevent="submit">
                <div class="flex items-center justify-between border-b border-slate-100 p-6">
                    <h2 class="text-lg font-semibold">
                        {{ selected ? 'Edit' : 'Create' }} {{ fields ? 'custom field' : 'event template' }}
                    </h2>
                    <button type="button" :aria-label="`Close ${singular} form`" @click="showForm = false">
                        <X class="size-5 text-slate-400" />
                    </button>
                </div>
                <div class="max-h-[65vh] space-y-5 overflow-y-auto p-6">
                    <template v-if="fields"
                        ><div>
                            <label for="field-label" class="workspace-label">Field label</label
                            ><input
                                id="field-label"
                                v-model="form.label"
                                class="workspace-input"
                                required
                                maxlength="255"
                                @input="
                                    !selected &&
                                    (form.field_id = form.label
                                        .toLowerCase()
                                        .replace(/[^a-z0-9]+/g, '_')
                                        .replace(/^_|_$/g, ''))
                                "
                            />
                        </div>
                        <div>
                            <label for="field-key" class="workspace-label">API field name</label
                            ><input
                                id="field-key"
                                v-model="form.field_id"
                                class="workspace-input font-mono"
                                :readonly="!!selected"
                                required
                            />
                            <p class="mt-2 text-xs text-slate-500">A stable name used in your event API.</p>
                        </div>
                        <div>
                            <label for="field-type" class="workspace-label">Field type</label
                            ><select id="field-type" v-model="form.type" class="workspace-input">
                                <option v-for="(label, value) in types" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div v-if="form.type === 'select'">
                            <label for="field-options" class="workspace-label">Options (one per line)</label
                            ><textarea
                                id="field-options"
                                :value="form.options.join('\n')"
                                class="workspace-input"
                                rows="4"
                                @input="form.options = $event.target.value.split('\n').filter(Boolean)"
                            />
                        </div>
                        <label class="flex items-center gap-3 text-sm"
                            ><input
                                v-model="form.required"
                                type="checkbox"
                                class="rounded text-emerald-700"
                            />Require this field when creating an event</label
                        ></template
                    ><template v-else
                        ><div>
                            <label for="template-name" class="workspace-label">Template name</label
                            ><input
                                id="template-name"
                                v-model="form.name"
                                class="workspace-input"
                                required
                                maxlength="255"
                            />
                        </div>
                        <div>
                            <label for="template-description" class="workspace-label"
                                >Description (optional)</label
                            ><textarea
                                id="template-description"
                                v-model="form.description"
                                class="workspace-input"
                                rows="2"
                            />
                        </div>
                        <fieldset>
                            <legend class="workspace-label">Choose your fields</legend>
                            <p v-if="loading" role="status" class="text-sm text-slate-500">Loading fields…</p>
                            <p v-else-if="loadError" role="alert" class="text-sm text-red-700">
                                {{ loadError
                                }}<button type="button" class="ml-2 underline" @click="load">Retry</button>
                            </p>
                            <p v-else-if="!available.length" class="text-sm text-slate-500">
                                Create a custom field first.
                            </p>
                            <div
                                v-for="field in available"
                                :key="field.id"
                                class="flex items-center gap-3 border-b border-slate-100 py-3"
                            >
                                <label class="flex flex-1 items-center gap-3 text-sm"
                                    ><input
                                        v-model="form.fields"
                                        type="checkbox"
                                        :value="field.id"
                                        class="rounded text-emerald-700"
                                    />{{ field.label
                                    }}<span class="text-xs text-slate-400">{{ field.type }}</span></label
                                ><select
                                    v-if="form.fields.includes(field.id)"
                                    :value="columns[field.id] || 0"
                                    :aria-label="`Column for ${field.label}`"
                                    class="workspace-input w-32"
                                    @change="columns[field.id] = Number($event.target.value)"
                                >
                                    <option :value="0">Left column</option>
                                    <option :value="1">Right column</option>
                                </select>
                            </div>
                        </fieldset></template
                    >
                    <p
                        v-for="(error, key) in form.errors"
                        :key="key"
                        role="alert"
                        class="text-sm text-red-700"
                    >
                        {{ error }}
                    </p>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-5">
                    <button type="button" class="workspace-secondary" @click="showForm = false">Cancel</button
                    ><button
                        class="workspace-primary"
                        :disabled="form.processing || (!fields && (loading || !form.fields.length))"
                    >
                        {{ form.processing ? 'Saving…' : selected ? 'Save changes' : `Create ${singular}` }}
                    </button>
                </div>
            </form></Modal
        ><ConfirmationModal :show="!!deleting" @close="deleting = null"
            ><template #title>Delete {{ singular }}?</template
            ><template #content
                >Remove {{ deleting?.name || deleting?.label }} from this workspace?
                <p v-if="deletion.errors.delete" role="alert" class="mt-3 text-red-700">
                    {{ deletion.errors.delete }}
                </p></template
            ><template #footer
                ><button class="workspace-secondary" @click="deleting = null">Cancel</button
                ><button class="workspace-primary ml-3" :disabled="deletion.processing" @click="remove">
                    Delete {{ singular }}
                </button></template
            ></ConfirmationModal
        ></AppLayout
    >
</template>
