<script setup>
import { computed, nextTick, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import ProviderSetupGuide from "@/Components/ProviderSetupGuide.vue";
import { integrationGuides } from "@/Lib/integrationGuides";
import ImportTemplateFields from "@/Components/ImportTemplateFields.vue";
import WebsiteEventImport from "@/Components/WebsiteEventImport.vue";
import { importTimezones, localTimezone } from "@/Lib/importTimezones";

const props = defineProps({
    connections: Array,
    eventTemplates: Array,
    fieldTemplates: Array,
    starterTemplates: Object,
});

const providers = {
    ics: integrationGuides.ics,
    csv: integrationGuides.csv,
    wordpress: integrationGuides.wordpress,
    eventbrite: integrationGuides.eventbrite,
};
const statusMessages = {
    connected: "Connected",
    queued: "Queued",
    syncing: "Importing",
    reconnect_required: "Reconnect required",
    error: "Import failed",
    disconnected: "Disconnected",
};
const editing = ref(null);
const settingsHeading = ref(null);
const provider = ref(null);
const preview = ref(null);
const previewError = ref("");
const previewing = ref(false);
const form = useForm({
    name: "",
    timezone: localTimezone,
    feed_url: "",
    api_key: "",
    organization_id: "",
    template_id: "",
    field_mapping: {},
    file: null,
});
const zones = computed(() => importTimezones(form.timezone));
const sourceFields = computed(() => props.starterTemplates[provider.value] ?? {});
const canCreateTemplate = computed(() => Object.keys(sourceFields.value).length > 0
    && !props.eventTemplates.some((template) => template.id === editing.value?.settings?.starter_template_id));
const action = useForm({});

async function open(key, existing = null) {
    form.reset();
    form.clearErrors();
    preview.value = null;
    previewError.value = "";
    editing.value = existing;
    provider.value = key;
    if (existing) {
        form.name = existing.name;
        form.timezone = existing.settings?.timezone || localTimezone;
        form.organization_id = existing.settings?.organization_id || "";
        form.template_id = existing.template_id || "";
        form.field_mapping = { ...(existing.field_mapping || {}) };
    } else if (key !== "csv") {
        form.name = providers[key].label;
    }
    await nextTick();
    settingsHeading.value?.focus();
}

function close() {
    form.reset();
    form.clearErrors();
    provider.value = null;
}

function submit() {
    form.transform((data) => data.template_id === "new"
        ? { ...data, template_id: null, create_template: true }
        : data);
    const options = { preserveScroll: true, onSuccess: close };
    if (provider.value === "csv") {
        form.post(route("integrations.imports.csv.store"), { ...options, forceFormData: true });
    } else if (editing.value) {
        form.put(route("integrations.imports.update", editing.value.id), options);
    } else {
        form.post(route("integrations.imports.store", provider.value), options);
    }
}

async function inspectCsv() {
    if (!form.file || previewing.value) return;
    const data = new FormData();
    data.append("file", form.file);
    data.append("timezone", form.timezone);
    previewError.value = "";
    preview.value = null;
    previewing.value = true;
    try {
        const response = await axios.post(route("integrations.imports.csv.preview"), data, {
            headers: { Accept: "application/json" },
        });
        preview.value = response.data;
    } catch (error) {
        previewError.value = error.response?.data?.message || "Could not preview the file.";
    } finally {
        previewing.value = false;
    }
}

function disconnect(connection) {
    if (window.confirm(`Disconnect ${connection.name}? Previously imported events stay in EventJunction.`)) {
        action.delete(route("integrations.destroy", connection.id), { preserveScroll: true });
    }
}
</script>

<template>
    <section id="event-imports" class="mt-6 scroll-mt-6" aria-label="Event imports">
        <div class="grid items-start gap-4 sm:grid-cols-2">
            <WebsiteEventImport :event-templates="eventTemplates" :field-templates="fieldTemplates" />
            <article v-for="(info, key) in providers" :key="key" class="workspace-panel flex min-w-0 flex-col items-start p-5">
                <h2 class="text-lg font-semibold">{{ info.label }}</h2>
                <p class="mb-4 mt-2 text-sm leading-6 text-slate-600">{{ info.text }}</p>
                <button class="workspace-secondary" type="button" :aria-expanded="provider === key" aria-controls="import-settings" @click="open(key)">
                    {{ key === 'csv' ? 'Choose file' : 'Add connection' }}
                </button>
                <ProviderSetupGuide class="mt-4" :provider="key" />
            </article>
        </div>

        <section v-if="provider" id="import-settings" class="workspace-panel mt-6 max-w-3xl space-y-5 p-5 sm:p-7" aria-labelledby="import-settings-heading">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h2 id="import-settings-heading" ref="settingsHeading" tabindex="-1" class="text-xl font-semibold">{{ editing ? 'Edit' : 'Set up' }} {{ providers[provider].label }}</h2>
                <button type="button" class="workspace-secondary" :disabled="form.processing || previewing" @click="close">Cancel</button>
            </div>
            <form class="space-y-4" @submit.prevent="submit">
                <label v-if="provider !== 'csv'" class="block font-medium">
                    Connection name
                    <input v-model="form.name" maxlength="255" required class="workspace-input mt-1 block w-full" />
                </label>
                <template v-if="provider === 'eventbrite'">
                    <label class="block font-medium">
                        Eventbrite organisation ID
                        <input v-model="form.organization_id" inputmode="numeric" required :readonly="!!editing" class="workspace-input mt-1 block w-full" />
                    </label>
                    <label class="block font-medium">
                        Personal OAuth token
                        <input v-model="form.api_key" type="password" autocomplete="off" :required="!editing || editing.status === 'disconnected'" class="workspace-input mt-1 block w-full" />
                    </label>
                </template>
                <label v-else-if="provider !== 'csv'" class="block font-medium">
                    {{ provider === 'ics' ? 'Calendar feed URL' : 'WordPress site URL' }}
                    <input v-model="form.feed_url" type="url" autocomplete="off" placeholder="https://" :required="!editing || editing.status === 'disconnected'" class="workspace-input mt-1 block w-full" />
                </label>
                <p v-if="editing" class="text-sm text-slate-600">Leave the connection address or token blank to keep the saved value.</p>
                <label v-if="provider === 'csv'" class="block font-medium">
                    CSV file
                    <input type="file" accept=".csv,text/csv" required class="workspace-input mt-1 block w-full" :disabled="previewing || form.processing" @change="form.file = $event.target.files[0] ?? null; preview = null; previewError = ''" />
                    <span class="mt-1 block text-sm font-normal text-slate-600"><code>title</code> is required. Optional columns are <code>starts_at</code>, <code>ends_at</code>, <code>timezone</code>, <code>location</code>, <code>description</code>, <code>all_day</code> and <code>external_id</code>. Use ISO dates.</span>
                </label>
                <label v-if="provider !== 'eventbrite'" class="block font-medium">
                    Timezone when the source doesn’t specify one
                    <select v-model="form.timezone" class="workspace-input mt-1 block w-full" :disabled="previewing || form.processing" @change="preview = null; previewError = ''">
                        <option v-for="zone in zones" :key="zone">{{ zone }}</option>
                    </select>
                </label>
                <ImportTemplateFields
                    v-model:template-id="form.template_id"
                    v-model:mapping="form.field_mapping"
                    :event-templates="eventTemplates"
                    :field-templates="fieldTemplates"
                    :source-fields="sourceFields"
                    :can-create-template="canCreateTemplate"
                />

                <p v-for="(message, key) in form.errors" :key="key" role="alert" class="text-sm text-red-700">{{ message }}</p>
                <p v-if="previewError" role="alert" class="text-sm text-red-700">{{ previewError }}</p>
                <div v-if="preview" aria-live="polite" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="font-semibold">{{ preview.count }} valid rows</h3>
                    <ul v-if="preview.errors.length" class="mt-2 list-disc pl-5 text-sm text-red-700">
                        <li v-for="error in preview.errors" :key="error">{{ error }}</li>
                    </ul>
                    <ul v-else class="mt-2 divide-y divide-slate-200 text-sm">
                        <li v-for="(row, index) in preview.sample" :key="index" class="py-2">
                            <strong>{{ row.title }}</strong>
                            <span class="block text-slate-600">{{ row.starts_at ? row.starts_at.replace('T', ' ') + ' · ' + row.timezone : 'No date' }}</span>
                        </li>
                    </ul>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button v-if="provider === 'csv'" type="button" class="workspace-secondary" :disabled="!form.file || previewing || form.processing" @click="inspectCsv">{{ previewing ? 'Checking file…' : 'Preview CSV' }}</button>
                    <button type="submit" class="workspace-primary" :disabled="form.processing || (provider === 'csv' && (!preview?.count || preview.errors.length))">
                        {{ provider === 'csv' ? 'Import drafts' : 'Save and queue import' }}
                    </button>
                </div>
            </form>
        </section>

        <section v-if="connections.length" class="mt-6 space-y-3" aria-labelledby="import-connections-heading">
            <h2 id="import-connections-heading" class="text-xl font-semibold">Connected imports</h2>
            <article v-for="connection in connections" :key="connection.id" class="workspace-panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="break-words font-semibold">{{ connection.name }}</h3>
                        <p class="text-sm text-slate-600">{{ providers[connection.provider].label }} · {{ statusMessages[connection.status] ?? connection.status }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button class="workspace-secondary" type="button" @click="open(connection.provider, connection)">Edit connection</button>
                        <button v-if="!['disconnected', 'reconnect_required'].includes(connection.status)" :disabled="action.processing || ['queued', 'syncing'].includes(connection.status)" class="workspace-secondary" type="button" @click="action.post(route('integrations.sync', connection.id), { preserveScroll: true })">Sync now</button>
                        <button v-if="connection.status !== 'disconnected'" :disabled="action.processing" class="workspace-secondary" type="button" @click="disconnect(connection)">Disconnect</button>
                    </div>
                </div>
                <p v-if="connection.last_synced_at" class="mt-3 text-xs text-slate-600">Last imported {{ new Date(connection.last_synced_at).toLocaleString() }}</p>
                <p v-if="connection.last_error" class="mt-2 text-sm text-red-700">{{ connection.last_error }}</p>
            </article>
        </section>
    </section>
</template>
