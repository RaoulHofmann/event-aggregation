<script setup>
import { ref, computed } from "vue";
import { useForm, usePoll } from "@inertiajs/vue3";
import {
    Plus,
    RefreshCw,
    X,
} from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import InboundImports from "@/Components/InboundImports.vue";
import ProviderSetupGuide from "@/Components/ProviderSetupGuide.vue";
import Modal from "@/Components/Modal.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
const props = defineProps({
    connections: Array,
    googleConfigured: Boolean,
    canManage: Boolean,
    eventTemplates: Array,
    fieldTemplates: Array,
    starterTemplates: Object,
});
usePoll(10000, { only: ["connections"] });
const showForm = ref(false),
    selected = ref(null),
    disconnecting = ref(null),
    form = useForm({
        name: "",
        api_key: "",
        template_id: "",
        field_mapping: {},
    }),
    action = useForm({});
const mappingFields = computed(() =>
    props.fieldTemplates.filter((f) =>
        props.eventTemplates
            .find((t) => t.id === Number(form.template_id))
            ?.fields?.includes(f.id),
    ),
);
const sourceFields = {
    title: "Event title",
    description: "Description",
    starts_at: "Start date/time",
    ends_at: "End date/time",
    timezone: "Timezone",
    location: "Location",
    status: "Status",
};
const labels = {
    pending: "Not yet synced",
    needs_configuration: "Choose calendars",
    reconnect_required: "Reconnect required",
    queued: "Sync queued",
    syncing: "Syncing",
    connected: "Up to date",
    error: "Needs attention",
    disconnected: "Disconnected",
};
const open = (record = null) => {
    selected.value = record;
    form.reset();
    form.clearErrors();
    form.name = record?.name || "My Humanitix account";
    form.template_id = record?.template_id || "";
    form.field_mapping = { ...(record?.field_mapping || {}) };
    showForm.value = true;
};
const save = () =>
        form[selected.value ? "put" : "post"](
            route(
                selected.value ? "integrations.update" : "integrations.store",
                selected.value?.id,
            ),
            {
                onSuccess: () => {
                    form.reset("api_key");
                    showForm.value = false;
                },
            },
        ),
    close = () => {
        form.reset("api_key");
        showForm.value = false;
    },
    disconnect = () =>
        action.delete(route("integrations.destroy", disconnecting.value.id), {
            onSuccess: () => (disconnecting.value = null),
        });
</script>
<template>
    <AppLayout title="Integrations">
        <h1 class="mb-8 text-3xl font-semibold">Integrations</h1>
        <section class="workspace-panel p-6 sm:p-8" aria-labelledby="humanitix-heading">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div class="flex min-w-0 flex-1 items-start gap-5">
                    <span
                        class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-xl font-bold text-orange-700"
                        aria-hidden="true"
                        >h.</span
                    >
                    <div class="min-w-0">
                        <h2 id="humanitix-heading" class="text-lg font-semibold">Humanitix</h2>
                        <p class="mt-2 text-sm text-slate-500">Read-only imports from your Humanitix host account.</p>
                    </div>
                </div>
                <button v-if="canManage" class="workspace-secondary" type="button" @click="open()">
                    <Plus class="size-4" />Connect Humanitix
                </button>
            </div>
            <ProviderSetupGuide class="mt-5 max-w-2xl" provider="humanitix" />
            <p v-if="!connections.some((c) => c.provider === 'humanitix')" class="mt-4 text-sm text-slate-500">
                {{ canManage ? "No Humanitix accounts connected." : "Ask your workspace owner to connect Humanitix." }}
            </p>
            <article
                v-for="c in connections.filter(
                    (c) => c.provider === 'humanitix',
                )"
                :key="c.id"
                class="mt-6 rounded-xl border border-slate-200 p-5"
            >
                <div class="flex flex-wrap justify-between gap-3">
                    <h3 class="font-semibold">{{ c.name }}</h3>
                    <span
                        class="rounded-full px-3 py-1 text-xs"
                        :class="
                            c.status === 'error'
                                ? 'bg-red-50 text-red-700'
                                : c.status === 'connected'
                                  ? 'bg-emerald-50 text-emerald-800'
                                  : 'bg-slate-100 text-slate-600'
                        "
                        >{{ labels[c.status] }}</span
                    >
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    Import template:
                    {{
                        eventTemplates.find((t) => t.id === c.template_id)
                            ?.name || "Provider default"
                    }}
                    · Last successful sync:
                    {{
                        c.last_synced_at
                            ? new Date(c.last_synced_at).toLocaleString()
                            : "Not synced yet"
                    }}
                </p>
                <p
                    v-if="c.last_error"
                    role="alert"
                    class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800"
                >
                    {{ c.last_error }}
                </p>
                <div
                    v-if="canManage"
                    class="mt-5 flex flex-wrap items-center gap-3"
                >
                    <button
                        v-if="c.status !== 'disconnected'"
                        class="workspace-secondary"
                        :disabled="
                            ['queued', 'syncing'].includes(c.status) ||
                            action.processing
                        "
                        @click="action.post(route('integrations.sync', c.id))"
                    >
                        <RefreshCw class="size-4" />Sync now</button
                    ><button
                        class="text-xs font-medium text-emerald-700"
                        @click="open(c)"
                    >
                        {{
                            c.status === "disconnected"
                                ? "Reconnect"
                                : "Template, mapping and API key"
                        }}</button
                    ><button
                        v-if="c.status !== 'disconnected'"
                        class="ml-auto text-xs text-red-700"
                        @click="disconnecting = c"
                    >
                        Disconnect
                    </button>
                </div>
            </article>
        </section>
        <section class="workspace-panel mt-6 p-6 sm:p-8" aria-labelledby="google-calendar-heading">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div class="flex items-start gap-5">
                    <span
                        class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-lg font-semibold text-blue-700"
                        aria-hidden="true"
                        >31</span
                    >
                    <div>
                        <h2 id="google-calendar-heading" class="text-lg font-semibold">Google Calendar</h2>
                        <p class="mt-2 text-sm text-slate-500">Read-only imports from selected Google calendars.</p>
                    </div>
                </div>
                <a
                    v-if="canManage && googleConfigured"
                    :href="route('integrations.google.connect')"
                    class="workspace-secondary"
                    >Connect Google</a
                >
                <p v-else-if="!googleConfigured" class="text-xs text-slate-500">
                    Google setup is needed on this installation.
                </p>
            </div>
            <ProviderSetupGuide class="mt-5 max-w-2xl" provider="google_calendar" />
            <article
                v-for="c in connections.filter(
                    (c) => c.provider === 'google_calendar',
                )"
                :key="c.id"
                class="mt-6 rounded-xl border border-slate-200 p-5"
            >
                <div class="flex flex-wrap justify-between gap-3">
                    <h3 class="font-semibold">{{ c.name }}</h3>
                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600"
                        >{{ labels[c.status] }}</span
                    >
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    {{
                        c.settings?.calendars
                            ?.map((calendar) => calendar.name)
                            .join(", ") || "No calendars selected"
                    }}
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    Import template:
                    {{
                        eventTemplates.find((t) => t.id === c.template_id)
                            ?.name || "Basic event"
                    }}
                    · Last successful sync:
                    {{
                        c.last_synced_at
                            ? new Date(c.last_synced_at).toLocaleString()
                            : "Not synced yet"
                    }}
                </p>
                <p
                    v-if="c.last_error"
                    role="alert"
                    class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800"
                >
                    {{ c.last_error }}
                </p>
                <div
                    v-if="canManage"
                    class="mt-5 flex flex-wrap items-center gap-3"
                >
                    <a
                        v-if="
                            ['disconnected', 'reconnect_required'].includes(
                                c.status,
                            )
                        "
                        :href="
                            route('integrations.google.connect', {
                                connection: c.id,
                            })
                        "
                        class="workspace-secondary"
                        >Reconnect Google</a
                    >
                    <template v-else>
                        <button
                            class="workspace-secondary"
                            :disabled="
                                [
                                    'queued',
                                    'syncing',
                                    'needs_configuration',
                                ].includes(c.status) || action.processing
                            "
                            @click="
                                action.post(route('integrations.sync', c.id))
                            "
                        >
                            <RefreshCw class="size-4" />Sync now
                        </button>
                        <a
                            :href="route('integrations.google.edit', c.id)"
                            class="text-xs font-medium text-emerald-700"
                            >Calendars, template and mapping</a
                        >
                    </template>
                    <button
                        v-if="c.status !== 'disconnected'"
                        class="ml-auto text-xs text-red-700"
                        @click="disconnecting = c"
                    >
                        Disconnect
                    </button>
                </div>
            </article>
        </section>
        <InboundImports
            v-if="canManage"
            :connections="connections.filter((c) => ['ics', 'wordpress', 'eventbrite'].includes(c.provider))"
            :event-templates="eventTemplates"
            :field-templates="fieldTemplates"
            :starter-templates="starterTemplates"
        />
        <Modal :show="showForm" max-width="2xl" @close="close"
            ><form class="bg-white" @submit.prevent="save">
                <div class="flex justify-between border-b p-6">
                    <h2 class="text-lg font-semibold">
                        {{
                            selected
                                ? "Update Humanitix connection"
                                : "Connect Humanitix"
                        }}
                    </h2>
                    <button
                        type="button"
                        aria-label="Close connection form"
                        @click="close"
                    >
                        <X class="size-5 text-slate-400" />
                    </button>
                </div>
                <div class="max-h-[65vh] space-y-5 overflow-y-auto p-6">
                    <div>
                        <label for="connection-name" class="workspace-label"
                            >Connection name</label
                        ><input
                            id="connection-name"
                            v-model="form.name"
                            required
                            class="workspace-input"
                            maxlength="255"
                        />
                    </div>
                    <div>
                        <label for="humanitix-key" class="workspace-label"
                            >Humanitix API key</label
                        ><input
                            id="humanitix-key"
                            v-model="form.api_key"
                            type="password"
                            autocomplete="off"
                            :required="
                                !selected || selected.status === 'disconnected'
                            "
                            class="workspace-input"
                        />
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            EventJunction only uses GET requests to Humanitix. Leave
                            the key blank to keep the current key when changing
                            the template or mapping.
                        </p>
                    </div>
                    <div>
                        <label for="import-template" class="workspace-label"
                            >Import into template</label
                        ><select
                            id="import-template"
                            v-model="form.template_id"
                            class="workspace-input"
                            @change="form.field_mapping = {}"
                        >
                            <option value="">Provider default</option>
                            <option
                                v-for="t in eventTemplates"
                                :key="t.id"
                                :value="t.id"
                            >
                                {{ t.name }}
                            </option>
                        </select>
                        <p class="mt-2 text-xs text-slate-500">
                            Built-in title, dates, timezone, status and location
                            are imported automatically. Map additional template
                            fields below. New mapping also applies to previously
                            imported records on the next sync, preserving local
                            overrides.
                        </p>
                    </div>
                    <fieldset v-if="mappingFields.length" class="space-y-3">
                        <legend class="workspace-label">
                            Match template fields to Humanitix
                        </legend>
                        <div v-for="f in mappingFields" :key="f.id">
                            <label
                                :for="`mapping-${f.id}`"
                                class="workspace-label"
                                >{{ f.label
                                }}{{
                                    f.required ? " (required when editing)" : ""
                                }}</label
                            ><select
                                :id="`mapping-${f.id}`"
                                v-model="form.field_mapping[f.field_id]"
                                class="workspace-input"
                            >
                                <option value="">
                                    Leave empty / fill locally
                                </option>
                                <option
                                    v-for="(label, key) in sourceFields"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </fieldset>
                    <p
                        v-for="(error, key) in form.errors"
                        :key="key"
                        role="alert"
                        class="text-sm text-red-700"
                    >
                        {{ error }}
                    </p>
                </div>
                <div class="flex justify-end gap-3 border-t bg-slate-50 p-5">
                    <button
                        type="button"
                        class="workspace-secondary"
                        @click="close"
                    >
                        Cancel</button
                    ><button
                        class="workspace-primary"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? "Saving…" : "Save and sync" }}
                    </button>
                </div>
            </form></Modal
        ><ConfirmationModal
            :show="!!disconnecting"
            @close="disconnecting = null"
            ><template #title>Disconnect Humanitix?</template
            ><template #content
                >Syncing will stop and the saved credentials will be removed.
                Previously imported events stay in EventJunction.</template
            ><template #footer
                ><button
                    class="workspace-secondary"
                    @click="disconnecting = null"
                >
                    Cancel</button
                ><button
                    class="workspace-primary ml-3"
                    :disabled="action.processing"
                    @click="disconnect"
                >
                    Disconnect
                </button></template
            ></ConfirmationModal
        ></AppLayout
    >
</template>
