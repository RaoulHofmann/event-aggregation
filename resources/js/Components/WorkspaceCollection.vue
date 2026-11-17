<script setup>
import { computed, ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import axios from "axios";
import {
    Layers,
    SlidersHorizontal,
    Plus,
    Search,
    Pencil,
    Trash2,
    X,
} from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "./Modal.vue";
import ConfirmationModal from "./ConfirmationModal.vue";
const props = defineProps({
    title: String,
    description: String,
    items: Array,
    resource: String,
    singular: String,
    fields: Boolean,
});
const query = ref(""),
    selected = ref(null),
    showForm = ref(false),
    deleting = ref(null),
    available = ref([]),
    loading = ref(false),
    loadError = ref(""),
    columns = ref({});
const fieldSearch = ref("");
const matchingFields = computed(() =>
    available.value.filter((f) =>
        `${f.label} ${f.type}`
            .toLowerCase()
            .includes(fieldSearch.value.toLowerCase()),
    ),
);
const previewFields = (column) =>
    available.value.filter(
        (f) =>
            form.fields.includes(f.id) &&
            Number(columns.value[f.id] || 0) === column,
    );
const form = useForm({
        name: "",
        description: "",
        label: "",
        field_id: "",
        type: "text",
        required: false,
        options: [],
        fields: [],
        layout: [[], []],
    }),
    deletion = useForm({});
const filtered = computed(() =>
    props.items.filter((i) =>
        (i.name || i.label).toLowerCase().includes(query.value.toLowerCase()),
    ),
);
const types = {
    text: "Text",
    textarea: "Long text",
    number: "Whole number",
    decimal: "Decimal",
    date: "Date",
    datetime: "Date and time",
    email: "Email address",
    url: "URL",
    select: "Select options",
    boolean: "Yes / no",
};
const load = async () => {
    loading.value = true;
    loadError.value = "";
    try {
        available.value = (await axios.get("/api/field-templates")).data.filter(
            (f) => !f.system_field || form.fields.includes(f.id),
        );
    } catch {
        loadError.value = "Could not load fields. Please retry.";
    } finally {
        loading.value = false;
    }
};
const open = (item = null) => {
    selected.value = item;
    fieldSearch.value = "";
    form.reset();
    form.clearErrors();
    if (item) Object.assign(form, JSON.parse(JSON.stringify(item)));
    if (!Array.isArray(form.options)) {
        try {
            form.options = JSON.parse(form.options || "[]");
        } catch {
            form.options = [];
        }
    }
    columns.value = Object.fromEntries(
        (form.layout || [[], []]).flatMap((c, i) => c.map((id) => [id, i])),
    );
    showForm.value = true;
    if (!props.fields) load();
};
const submit = () => {
    if (!props.fields)
        form.layout = [0, 1].map((c) =>
            form.fields.filter((id) => Number(columns.value[id] || 0) === c),
        );
    form[selected.value ? "put" : "post"](
        route(
            `${props.resource}.${selected.value ? "update" : "store"}`,
            selected.value?.id,
        ),
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
                <h1
                    class="text-3xl font-semibold tracking-tight text-slate-950"
                >
                    {{ title }}
                </h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">
                    {{ description }}
                </p>
            </div>
            <button class="workspace-primary" @click="open()">
                <Plus class="size-4" />Create {{ singular }}
            </button>
        </div>
        <section class="workspace-panel overflow-hidden">
            <div
                class="flex items-center justify-between border-b border-slate-100 p-5"
            >
                <div class="relative w-full max-w-sm">
                    <Search
                        class="absolute left-3 top-3 size-4 text-slate-400"
                    /><label :for="`${resource}-search`" class="sr-only"
                        >Search {{ title.toLowerCase() }}</label
                    ><input
                        :id="`${resource}-search`"
                        v-model="query"
                        type="search"
                        :placeholder="`Search ${title.toLowerCase()}…`"
                        class="workspace-input pl-10"
                    />
                </div>
                <span class="ml-3 text-xs text-slate-500"
                    >{{ items.length }} total</span
                >
            </div>
            <div v-if="!filtered.length" class="px-6 py-16 text-center">
                <component
                    :is="fields ? SlidersHorizontal : Layers"
                    class="mx-auto mb-4 size-10 text-emerald-700"
                />
                <h2 class="font-semibold">
                    {{
                        query
                            ? "No matching results"
                            : `Create your first ${singular}`
                    }}
                </h2>
                <p
                    class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500"
                >
                    {{
                        fields
                            ? "Define the details your events need, then reuse them in templates."
                            : "Combine custom fields into a reusable structure for your events."
                    }}
                </p>
                <button
                    v-if="!query"
                    class="workspace-primary mt-6"
                    @click="open()"
                >
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
                    <h2 class="truncate text-sm font-semibold">
                        {{ item.label || item.name }}
                    </h2>
                    <p class="mt-1 truncate text-xs text-slate-500">
                        {{
                            fields
                                ? item.type
                                : `${item.fields?.length || 0} custom fields`
                        }}<span v-if="item.description">
                            · {{ item.description }}</span
                        ><span v-if="item.required"> · Required</span>
                    </p>
                </div>
                <span
                    v-if="item.system_field || item.source === 'humanitix'"
                    class="text-xs text-slate-500"
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
        <div v-if="!fields" class="mt-4 flex flex-wrap items-center gap-2">
            <span class="text-xs text-slate-500">Start a template:</span
            ><button
                v-for="name in ['Workshop', 'Community event', 'Performance']"
                :key="name"
                class="workspace-secondary"
                @click="
                    open();
                    form.name = name;
                    form.description = `Custom information for a ${name.toLowerCase()}. Standard event details are built in.`;
                "
            >
                {{ name }}
            </button>
        </div>
        <Modal
            :show="showForm"
            :max-width="fields ? '5xl' : '7xl'"
            @close="showForm = false"
            ><form class="bg-white" @submit.prevent="submit">
                <div
                    class="flex items-center justify-between border-b border-slate-100 p-6"
                >
                    <h2 class="text-lg font-semibold">
                        {{ selected ? "Edit" : "Create" }}
                        {{ fields ? "custom field" : "event template" }}
                    </h2>
                    <button
                        type="button"
                        :aria-label="`Close ${singular} form`"
                        @click="showForm = false"
                    >
                        <X class="size-5 text-slate-400" />
                    </button>
                </div>
                <div
                    class="max-h-[calc(100dvh-13rem)] overflow-y-auto p-5 sm:p-8"
                >
                    <div
                        v-if="fields"
                        class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]"
                    >
                        <section class="min-w-0 space-y-6">
                            <div>
                                <h3 class="font-semibold">Field details</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Define it once, then use it across your
                                    event templates.
                                </p>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label
                                        for="field-label"
                                        class="workspace-label"
                                        >Field label</label
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
                                    <label
                                        for="field-key"
                                        class="workspace-label"
                                        >API field name</label
                                    ><input
                                        id="field-key"
                                        v-model="form.field_id"
                                        class="workspace-input font-mono"
                                        :readonly="!!selected"
                                        required
                                    />
                                    <p class="mt-2 text-xs text-slate-500">
                                        Stable name for your unified API.
                                    </p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label
                                        for="field-type"
                                        class="workspace-label"
                                        >Field type</label
                                    ><select
                                        id="field-type"
                                        v-model="form.type"
                                        class="workspace-input"
                                    >
                                        <option
                                            v-for="(label, value) in types"
                                            :key="value"
                                            :value="value"
                                        >
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="form.type === 'select'">
                                <label
                                    for="field-options"
                                    class="workspace-label"
                                    >Options (one per line)</label
                                ><textarea
                                    id="field-options"
                                    :value="form.options.join('\n')"
                                    class="workspace-input min-h-48 font-mono"
                                    rows="8"
                                    @input="
                                        form.options = $event.target.value
                                            .split('\n')
                                            .filter(Boolean)
                                    "
                                />
                            </div>
                            <label
                                class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 text-sm"
                                ><input
                                    v-model="form.required"
                                    type="checkbox"
                                    class="mt-1 rounded text-emerald-700"
                                /><span class="font-medium"
                                    >Require this field when creating an
                                    event<span
                                        class="mt-1 block font-normal text-slate-500"
                                        >Optional fields can be left
                                        empty.</span
                                    ></span
                                ></label
                            >
                        </section>
                        <fieldset disabled class="rounded-xl bg-slate-50 p-6">
                            <p class="workspace-eyebrow mb-6">Field preview</p>
                            <label class="workspace-label"
                                >{{ form.label || "Your field label"
                                }}{{ form.required ? " *" : "" }}</label
                            ><textarea
                                v-if="form.type === 'textarea'"
                                class="workspace-input"
                                placeholder="An organiser's answer…"
                                rows="5"
                            /><select
                                v-else-if="form.type === 'select'"
                                class="workspace-input"
                            >
                                <option value="">Choose an option</option>
                                <option
                                    v-for="option in form.options"
                                    :key="option"
                                >
                                    {{ option }}
                                </option></select
                            ><label
                                v-else-if="form.type === 'boolean'"
                                class="flex items-center gap-2 text-sm"
                                ><input
                                    type="checkbox"
                                    class="rounded text-emerald-700"
                                />Yes</label
                            ><input
                                v-else
                                class="workspace-input"
                                :type="
                                    {
                                        datetime: 'datetime-local',
                                        decimal: 'number',
                                        image: 'url',
                                        number: 'number',
                                        date: 'date',
                                        email: 'email',
                                        url: 'url',
                                    }[form.type] || 'text'
                                "
                                :placeholder="
                                    form.label || 'An organiser’s answer'
                                "
                            />
                            <p class="mt-5 text-xs leading-5 text-slate-500">
                                This is how the field will appear in the event
                                editor. Preview answers are not saved.
                            </p>
                        </fieldset>
                    </div>
                    <div
                        v-else
                        class="grid items-start gap-8 lg:grid-cols-[minmax(260px,1fr)_minmax(0,2fr)]"
                    >
                        <section class="min-w-0 space-y-6 lg:sticky lg:top-0">
                            <div>
                                <h3 class="font-semibold">Template settings</h3>
                                <p
                                    class="mt-1 text-sm leading-6 text-slate-500"
                                >
                                    Choose the extra information this type of
                                    event needs.
                                </p>
                            </div>
                            <div>
                                <label
                                    for="template-name"
                                    class="workspace-label"
                                    >Template name</label
                                ><input
                                    id="template-name"
                                    v-model="form.name"
                                    class="workspace-input"
                                    required
                                    maxlength="255"
                                />
                            </div>
                            <div>
                                <label
                                    for="template-description"
                                    class="workspace-label"
                                    >Description (optional)</label
                                ><textarea
                                    id="template-description"
                                    v-model="form.description"
                                    class="workspace-input"
                                    rows="5"
                                />
                            </div>
                            <div class="rounded-xl bg-slate-50 p-5">
                                <p class="text-sm font-medium">
                                    Standard details are included
                                </p>
                                <p
                                    class="mt-2 text-sm leading-6 text-slate-500"
                                >
                                    Title, description, schedule, repeat
                                    settings, timezone, location and status are
                                    built in.
                                </p>
                                <p
                                    class="mt-4 text-sm font-medium text-emerald-800"
                                >
                                    {{ form.fields.length }} custom fields
                                    selected
                                </p>
                            </div>
                        </section>
                        <section class="min-w-0 space-y-6">
                            <fieldset
                                class="rounded-xl border border-slate-200 p-5"
                            >
                                <legend class="px-2 font-semibold">
                                    Choose your fields
                                </legend>
                                <input
                                    v-model="fieldSearch"
                                    type="search"
                                    aria-label="Search available fields"
                                    placeholder="Find a custom field…"
                                    class="workspace-input mb-4"
                                />
                                <p
                                    v-if="loading"
                                    role="status"
                                    class="text-sm text-slate-500"
                                >
                                    Loading fields…
                                </p>
                                <p
                                    v-else-if="loadError"
                                    role="alert"
                                    class="text-sm text-red-700"
                                >
                                    {{ loadError }}
                                    <button
                                        type="button"
                                        class="underline"
                                        @click="load"
                                    >
                                        Retry
                                    </button>
                                </p>
                                <p
                                    v-else-if="!available.length"
                                    class="text-sm text-slate-500"
                                >
                                    Add custom fields when you need more than
                                    the standard details.
                                </p>
                                <p
                                    v-else-if="!matchingFields.length"
                                    class="text-sm text-slate-500"
                                >
                                    No fields match this search.
                                </p>
                                <div class="max-h-72 overflow-y-auto pr-1">
                                    <div
                                        v-for="field in matchingFields"
                                        :key="field.id"
                                        class="flex flex-wrap items-center gap-3 border-b border-slate-100 py-4 last:border-0"
                                    >
                                        <label
                                            class="flex min-w-0 flex-1 items-center gap-3 text-sm"
                                            ><input
                                                v-model="form.fields"
                                                type="checkbox"
                                                :value="field.id"
                                                class="rounded text-emerald-700"
                                            /><span
                                                class="min-w-0 break-words font-medium"
                                                >{{ field.label
                                                }}<span
                                                    class="mt-1 block text-xs font-normal text-slate-500"
                                                    >{{
                                                        types[field.type] ||
                                                        field.type
                                                    }}{{
                                                        field.required
                                                            ? " · Required"
                                                            : ""
                                                    }}</span
                                                ></span
                                            ></label
                                        ><select
                                            v-if="
                                                form.fields.includes(field.id)
                                            "
                                            :value="columns[field.id] || 0"
                                            :aria-label="`Column for ${field.label}`"
                                            class="workspace-input w-36 shrink-0"
                                            @change="
                                                columns[field.id] = Number(
                                                    $event.target.value,
                                                )
                                            "
                                        >
                                            <option :value="0">
                                                Left column
                                            </option>
                                            <option :value="1">
                                                Right column
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                            <section class="rounded-xl bg-slate-50 p-5">
                                <div
                                    class="mb-5 flex items-center justify-between gap-3"
                                >
                                    <h3 class="text-sm font-semibold">
                                        Event form layout
                                    </h3>
                                    <span class="text-xs text-slate-500"
                                        >Custom fields preview</span
                                    >
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div
                                        v-for="column in [0, 1]"
                                        :key="column"
                                        class="min-w-0 space-y-4"
                                    >
                                        <p
                                            class="text-xs font-medium uppercase tracking-wider text-slate-400"
                                        >
                                            {{
                                                column === 0 ? "Left" : "Right"
                                            }}
                                            column
                                        </p>
                                        <p
                                            v-if="!previewFields(column).length"
                                            class="rounded-lg border border-dashed border-slate-300 p-5 text-xs text-slate-400"
                                        >
                                            No fields placed here
                                        </p>
                                        <div
                                            v-for="f in previewFields(column)"
                                            :key="f.id"
                                        >
                                            <p class="mb-2 text-xs font-medium">
                                                {{ f.label
                                                }}{{ f.required ? " *" : "" }}
                                            </p>
                                            <div
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-3 text-xs text-slate-400"
                                            >
                                                {{ types[f.type] || f.type }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </section>
                    </div>
                    <p
                        v-for="(error, key) in form.errors"
                        :key="key"
                        role="alert"
                        class="text-sm text-red-700"
                    >
                        {{ error }}
                    </p>
                </div>
                <div
                    class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-5"
                >
                    <button
                        type="button"
                        class="workspace-secondary"
                        @click="showForm = false"
                    >
                        Cancel</button
                    ><button
                        class="workspace-primary"
                        :disabled="form.processing || (!fields && loading)"
                    >
                        {{
                            form.processing
                                ? "Saving…"
                                : selected
                                  ? "Save changes"
                                  : `Create ${singular}`
                        }}
                    </button>
                </div>
            </form></Modal
        ><ConfirmationModal :show="!!deleting" @close="deleting = null"
            ><template #title>Delete {{ singular }}?</template
            ><template #content
                >Remove {{ deleting?.name || deleting?.label }} from this
                workspace?
                <p
                    v-if="deletion.errors.delete"
                    role="alert"
                    class="mt-3 text-red-700"
                >
                    {{ deletion.errors.delete }}
                </p></template
            ><template #footer
                ><button class="workspace-secondary" @click="deleting = null">
                    Cancel</button
                ><button
                    class="workspace-primary ml-3"
                    :disabled="deletion.processing"
                    @click="remove"
                >
                    Delete {{ singular }}
                </button></template
            ></ConfirmationModal
        ></AppLayout
    >
</template>
