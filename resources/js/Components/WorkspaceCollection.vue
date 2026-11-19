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
import EditorChrome from "./EditorChrome.vue";
import Modal from "./Modal.vue";
import ConfirmationModal from "./ConfirmationModal.vue";
const props = defineProps({
    title: String,
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
const selectedFieldsOnly = ref(false);
const matchingFields = computed(() =>
    available.value.filter(
        (f) =>
            (!selectedFieldsOnly.value || form.fields.includes(f.id)) &&
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
    selectedFieldsOnly.value = false;
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
                <h1
                    class="text-3xl font-semibold tracking-tight text-slate-950"
                >
                    {{ title }}
                </h1>
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
            ><form
                class="collection-editor bg-white"
                :class="{ 'is-template-editor': !fields }"
                @submit.prevent="submit"
            >
                <EditorChrome
                    :context="fields ? 'Custom fields' : 'Event templates'"
                    :name="
                        fields
                            ? form.label || 'New field'
                            : form.name || 'New template'
                    "
                    :title="`${selected ? 'Edit' : 'Create'} ${fields ? 'custom field' : 'event template'}`"
                    :close-label="`Close ${singular} form`"
                    @close="showForm = false"
                />
                <div
                    class="collection-body max-h-[calc(100dvh-17rem)] overflow-y-auto p-5 sm:p-8"
                >
                    <div
                        v-if="fields"
                        class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]"
                    >
                        <section class="min-w-0 space-y-6">
                            <h3 class="font-semibold">Field details</h3>
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
                                Preview answers are not saved.
                            </p>
                        </fieldset>
                    </div>
                    <div
                        v-else
                        class="grid items-start gap-8 lg:grid-cols-[minmax(260px,1fr)_minmax(0,2fr)]"
                    >
                        <section class="min-w-0 space-y-6 lg:sticky lg:top-0">
                            <h3 class="font-semibold">Template settings</h3>
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
                                <div class="field-view-switch">
                                    <button
                                        type="button"
                                        aria-label="Show all fields"
                                        :aria-pressed="!selectedFieldsOnly"
                                        @click="selectedFieldsOnly = false"
                                    >
                                        All fields</button
                                    ><button
                                        type="button"
                                        aria-label="Show selected fields"
                                        :aria-pressed="selectedFieldsOnly"
                                        :disabled="!form.fields.length"
                                        @click="selectedFieldsOnly = true"
                                    >
                                        Selected ({{ form.fields.length }})
                                    </button>
                                </div>
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
                                <div
                                    class="field-choice-list max-h-72 overflow-y-auto"
                                >
                                    <div
                                        v-for="field in matchingFields"
                                        :key="field.id"
                                        class="field-choice-row"
                                    >
                                        <label class="field-choice-label"
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

<style scoped>
.collection-editor {
    color: #38323e;
}
.is-template-editor .collection-body {
    background: #fffdf9;
}
.collection-editor .workspace-input {
    border-color: #ddd6e5;
    box-shadow: 0 1px 2px #40344f03;
    background: #fff;
    border-radius: 5px;
}
.collection-editor .workspace-input:focus {
    border-color: #a48bae;
    --tw-ring-color: #a48bae;
}
.collection-editor .workspace-label {
    color: #655a70;
    font-weight: 500;
}
.is-template-editor .collection-body > div > section:first-child {
    background: #fbf5e5;
    padding: 25px;
    border: 1px solid #ece1c6;
    border-radius: 6px;
}
.is-template-editor fieldset {
    border-color: #e2dbe8;
    border-radius: 6px;
    background: #fff;
}
.is-template-editor .collection-body > div > section:last-child > section {
    background: #f5f1f8;
    border: 1px solid #e2dbe8;
    border-radius: 6px;
}
.collection-editor .workspace-primary {
    background: #4f435b;
    font-weight: 600;
}
.collection-editor .workspace-primary:hover {
    background: #665574;
}
.collection-editor .workspace-secondary {
    font-weight: 500;
}
.collection-editor input[type="checkbox"] {
    color: #71607e;
    accent-color: #71607e;
}
.is-template-editor .text-emerald-800 {
    color: #8d759d;
}
.is-template-editor .bg-slate-50 {
    background: #f9f2df;
}
.collection-editor > div:last-child {
    background: #faf8fc;
    border-color: #e6e0eb;
}
.collection-editor h3 {
    font-weight: 650;
    letter-spacing: -0.25px;
}
.collection-editor .text-slate-500 {
    color: #8b7e95;
}
.is-template-editor .collection-body > div {
    gap: 25px;
}
@media (max-width: 640px) {
    .is-template-editor .collection-body > div > section:first-child {
        padding: 20px;
    }
    .collection-body {
        max-height: calc(100dvh - 270px);
    }
}
</style>

<style scoped>
.field-view-switch {
    display: flex;
    gap: 7px;
    margin-bottom: 16px;
}
.field-view-switch button {
    font-size: 11px;
    padding: 6px 10px;
    border: 1px solid #e2d9e9;
    border-radius: 4px;
    color: #8b7899;
}
.field-view-switch button[aria-pressed="true"] {
    background: #f1eaf6;
    color: #6b527b;
    border-color: #cdbbd9;
}
.field-view-switch button:disabled {
    opacity: 0.5;
}
</style>

<style scoped>
.field-choice-list {
    padding: 6px 8px;
    scroll-padding: 8px;
}
.field-choice-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px;
    min-height: 70px;
    padding: 15px 0;
    border-bottom: 1px solid #eee8f2;
}
.field-choice-row:last-child {
    border-bottom: 0;
}
.field-choice-label {
    display: grid;
    grid-template-columns: 20px minmax(0, 1fr);
    align-items: center;
    gap: 12px;
    min-width: 0;
    min-height: 40px;
    font-size: 14px;
    line-height: 1.5;
    cursor: pointer;
}
.field-choice-label input[type="checkbox"] {
    display: block;
    position: static;
    flex: none;
    width: 18px;
    height: 18px;
    min-width: 18px;
    min-height: 18px;
    margin: 0;
    border-radius: 4px;
    --tw-ring-color: #a48bae;
}
.field-choice-label > span {
    display: block;
    min-width: 0;
    overflow-wrap: anywhere;
}
.field-choice-label > span > span {
    line-height: 1.5;
    margin-top: 4px;
}
.field-choice-row > select {
    width: 144px;
    min-width: 0;
}
@media (max-width: 640px) {
    .field-choice-row {
        grid-template-columns: minmax(0, 1fr);
        gap: 8px;
        padding: 14px 0;
    }
    .field-choice-row > select {
        width: calc(100% - 32px);
        max-width: calc(100% - 32px);
        margin-left: 32px;
    }
    .field-choice-list {
        padding: 6px;
    }
}
</style>
