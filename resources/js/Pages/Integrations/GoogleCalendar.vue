<script setup>
import { computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ArrowLeft, ShieldCheck } from "lucide-vue-next";
const props = defineProps({
    connection: Object,
    calendars: Array,
    eventTemplates: Array,
    fieldTemplates: Array,
});
const form = useForm({
    name: props.connection.name,
    calendars: (props.connection.settings?.calendars || []).map((c) => c.id),
    template_id: props.connection.template_id || "",
    field_mapping: { ...(props.connection.field_mapping || {}) },
});
const fields = computed(() =>
    props.fieldTemplates.filter((f) =>
        props.eventTemplates
            .find((t) => t.id === Number(form.template_id))
            ?.fields?.includes(f.id),
    ),
);
const sources = {
    title: "Event title",
    description: "Description",
    starts_at: "Start date/time",
    ends_at: "End date/time",
    timezone: "Timezone",
    location: "Location",
    status: "Import status (draft)",
};
const save = () =>
    form.put(route("integrations.google.update", props.connection.id));
</script>
<template>
    <AppLayout title="Google Calendar import">
        <Link
            :href="route('integrations.index')"
            class="mb-6 inline-flex items-center gap-2 text-sm text-slate-500"
            ><ArrowLeft class="size-4" />Integrations</Link
        >
        <div class="mb-8">
            <p class="workspace-eyebrow mb-2">Google Calendar · inbound only</p>
            <h1 class="text-3xl font-semibold">Choose what comes in.</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                Select calendars and an event template. Google supplies the
                dates and details; your team can edit and publish the imported
                drafts in EventJunction.
            </p>
        </div>
        <form
            class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,.55fr)]"
            @submit.prevent="save"
        >
            <div class="workspace-panel space-y-7 p-6 sm:p-8">
                <div>
                    <label for="google-name" class="workspace-label"
                        >Connection name</label
                    ><input
                        id="google-name"
                        v-model="form.name"
                        required
                        maxlength="255"
                        class="workspace-input"
                    />
                </div>
                <fieldset>
                    <legend class="mb-3 font-semibold">
                        Calendars to import
                    </legend>
                    <p class="mb-4 text-sm text-slate-500">
                        Choose up to 10 calendars you can read.
                    </p>
                    <label
                        v-for="calendar in calendars"
                        :key="calendar.id"
                        class="mb-3 flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4"
                    >
                        <input
                            v-model="form.calendars"
                            type="checkbox"
                            :value="calendar.id"
                            class="mt-1 size-4 shrink-0 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600"
                        />
                        <span class="min-w-0"
                            ><span class="block break-words text-sm font-medium"
                                >{{ calendar.name
                                }}<span
                                    v-if="calendar.primary"
                                    class="ml-2 text-xs text-slate-500"
                                    >Primary</span
                                ></span
                            ><span class="mt-1 block text-xs text-slate-500">{{
                                calendar.timezone
                            }}</span></span
                        >
                    </label>
                    <p v-if="!calendars.length" class="text-sm text-slate-500">
                        No readable calendars were returned. Check your Google
                        permissions.
                    </p>
                </fieldset>
                <div>
                    <label for="google-template" class="workspace-label"
                        >Import into template</label
                    ><select
                        id="google-template"
                        v-model="form.template_id"
                        class="workspace-input"
                        @change="form.field_mapping = {}"
                    >
                        <option value="">Basic event — no custom fields</option>
                        <option
                            v-for="template in eventTemplates"
                            :key="template.id"
                            :value="template.id"
                        >
                            {{ template.name }}
                        </option>
                    </select>
                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        Title, dates, timezone, description and location are
                        imported automatically. Map any additional fields below.
                    </p>
                </div>
                <fieldset v-if="fields.length" class="space-y-4">
                    <legend class="mb-3 font-semibold">
                        Match template fields
                    </legend>
                    <div v-for="field in fields" :key="field.id">
                        <label
                            :for="`google-map-${field.id}`"
                            class="workspace-label"
                            >{{ field.label
                            }}{{
                                field.required ? " (required when editing)" : ""
                            }}</label
                        ><select
                            :id="`google-map-${field.id}`"
                            v-model="form.field_mapping[field.field_id]"
                            class="workspace-input"
                        >
                            <option value="">Fill in EventJunction</option>
                            <option
                                v-for="(label, key) in sources"
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
                <div
                    class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-6"
                >
                    <Link
                        :href="route('integrations.index')"
                        class="workspace-secondary"
                        >Cancel</Link
                    ><button
                        class="workspace-primary"
                        :disabled="form.processing || !form.calendars.length"
                    >
                        {{ form.processing ? "Saving…" : "Save and import" }}
                    </button>
                </div>
            </div>
            <aside
                class="workspace-panel space-y-4 p-6 text-sm leading-6 text-slate-500"
            >
                <ShieldCheck class="size-6 text-emerald-700" />
                <h2 class="font-semibold text-slate-800">
                    Your work stays here.
                </h2>
                <p>
                    Google Calendar access is read-only. Creating, editing or
                    deleting an event in EventJunction never changes Google.
                </p>
                <p>
                    New imports are drafts. Review them before publishing
                    through your API.
                </p>
                <p>
                    Repeating events arrive as individual occurrences, including
                    Google exceptions. Imports cover the past 90 days through
                    the next 12 months.
                </p>
                <p>
                    Changed fields and custom answers are protected on refresh.
                    Removing a calendar keeps its imported events.
                </p>
                <p>
                    Refreshes are scheduled every 15 minutes while the server
                    and worker are running. You can also use Sync now.
                </p>
            </aside>
        </form>
    </AppLayout>
</template>
