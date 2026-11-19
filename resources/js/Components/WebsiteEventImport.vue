<script setup>
import { computed, ref, watch } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import axios from "axios";
import ImportTemplateFields from "@/Components/ImportTemplateFields.vue";
import { importTimezones, localTimezone } from "@/Lib/importTimezones";

defineProps({ eventTemplates: Array, fieldTemplates: Array });

const url = ref("");
const timezone = ref(localTimezone);
const zones = computed(() => importTimezones(timezone.value));
const event = ref(null);
const previewing = ref(false);
const previewError = ref("");
const form = useForm({ preview: "", template_id: "", field_mapping: {} });

watch([url, timezone], () => {
    event.value = null;
    previewError.value = "";
    form.preview = "";
    form.clearErrors();
});

async function preview() {
    if (previewing.value || form.processing) return;
    previewError.value = "";
    event.value = null;
    form.preview = "";
    form.clearErrors();
    previewing.value = true;
    try {
        const { data } = await axios.post(route("integrations.imports.website.preview"), {
            url: url.value,
            timezone: timezone.value,
        }, { headers: { Accept: "application/json" } });
        event.value = data.event;
        form.preview = data.preview;
    } catch (error) {
        previewError.value = error.response?.data?.errors?.url?.[0]
            || error.response?.data?.errors?.timezone?.[0]
            || "Could not preview this event. Try again in a moment.";
    } finally {
        previewing.value = false;
    }
}

function save() {
    if (!form.preview || previewing.value || form.processing) return;
    form.post(route("integrations.imports.website.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            event.value = null;
            url.value = "";
        },
    });
}

function formatDate(date) {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        ...(event.value.all_day ? {} : { timeStyle: "short" }),
        timeZone: event.value.timezone,
    }).format(new Date(date));
}

const dateLabel = computed(() => {
    const start = formatDate(event.value.starts_at);
    if (!event.value.ends_at) return start;
    const end = formatDate(event.value.all_day ? Date.parse(event.value.ends_at) - 1 : event.value.ends_at);
    return end === start ? start : `${start} – ${end}`;
});
</script>

<template>
    <article id="website-import" class="workspace-panel min-w-0 scroll-mt-6 p-5 sm:col-span-2" aria-labelledby="website-import-heading">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <h2 id="website-import-heading" class="text-lg font-semibold">Import an event from a website</h2>
            <span class="rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-900">Work in progress</span>
        </div>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Paste a public link to one event. This works with sites that include structured event details.
            We’re looking for feedback on which sites work and which ones you’d like us to support.
            <Link :href="route('dashboard', { feedback: 'website-import' }) + '#support'" class="font-medium text-emerald-800 underline underline-offset-2">Send feedback</Link>
        </p>

        <form class="mt-4 space-y-3" @submit.prevent="preview">
            <div class="grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,16rem)_auto]">
                <label class="block text-sm font-medium">
                    Event URL
                    <input v-model="url" type="url" maxlength="2047" autocomplete="off" placeholder="https://" required :disabled="previewing || form.processing" class="workspace-input mt-1 block w-full" />
                </label>
                <label class="block text-sm font-medium">
                    Display and fallback timezone
                    <select v-model="timezone" :disabled="previewing || form.processing" class="workspace-input mt-1 block w-full">
                        <option v-for="zone in zones" :key="zone">{{ zone }}</option>
                    </select>
                </label>
                <button type="submit" class="workspace-secondary" :disabled="previewing || form.processing">{{ previewing ? 'Checking link…' : 'Preview event' }}</button>
            </div>
            <p v-if="previewError" role="alert" class="text-sm text-red-700">{{ previewError }}</p>
        </form>

        <form v-if="event" class="mt-4 space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4" @submit.prevent="save">
            <div aria-live="polite">
                <h3 class="break-words font-semibold">{{ event.title }}</h3>
                <p class="mt-1 text-sm text-slate-600">
                    {{ dateLabel }}
                    <template v-if="event.all_day"> · All day</template>
                    · {{ event.timezone }}
                </p>
                <p v-if="event.location" class="mt-1 break-words text-sm text-slate-600">{{ event.location }}</p>
                <a :href="event.url" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-sm font-medium text-emerald-800 underline underline-offset-2">Original listing</a>
            </div>
            <fieldset :disabled="form.processing">
                <ImportTemplateFields
                    v-model:template-id="form.template_id"
                    v-model:mapping="form.field_mapping"
                    :event-templates="eventTemplates"
                    :field-templates="fieldTemplates"
                    :source-fields="{ source_url: { label: 'Original listing URL' } }"
                    :unavailable-sources="['description']"
                />
            </fieldset>
            <p v-for="(message, key) in form.errors" :key="key" role="alert" class="text-sm text-red-700">{{ message }}</p>
            <button type="submit" class="workspace-primary" :disabled="form.processing">Save as draft</button>
        </form>
    </article>
</template>
