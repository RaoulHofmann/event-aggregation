<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    widget: Object,
    embedUrl: String,
    templates: Array,
    sources: Array,
    appearance: Object,
    themes: Object,
});
const sourceNames = {
    eventnest: "EventJunction",
    humanitix: "Humanitix",
    google_calendar: "Google Calendar",
    ics: "Calendar feed",
    csv: "CSV import",
    wordpress: "WordPress",
    eventbrite: "Eventbrite",
    website: "Website",
};
const form = useForm({
    title: props.widget?.title ?? "Upcoming events",
    source: props.widget?.source ?? "",
    template_id: props.widget?.template_id ?? "",
    appearance: { ...props.appearance },
});
const disableForm = useForm({});
const copied = ref(false);
const embedCode = ref(null);
const snippet = computed(() => props.embedUrl
    ? `<iframe src="${props.embedUrl}" title="Upcoming events" loading="lazy" width="100%" height="420" style="border:0"></iframe>`
    : "");
const previewEvent = ref(null);
const previewVersion = ref(0);
const previewUrl = computed(() => route("events.widget.preview", {
    title: form.title,
    appearance: {
        ...form.appearance,
        compact: Number(form.appearance.compact),
        show_description: Number(form.appearance.show_description),
        show_credit: Number(form.appearance.show_credit),
    },
    event: previewEvent.value || undefined,
}, false));
const preview = ref(previewUrl.value);
let previewTimer;
watch(() => [form.title, { ...form.appearance }], () => {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(() => { preview.value = previewUrl.value; }, 260);
});
onBeforeUnmount(() => clearTimeout(previewTimer));

function showPreview(event = null) {
    clearTimeout(previewTimer);
    previewEvent.value = event;
    preview.value = previewUrl.value;
    previewVersion.value++;
}

function syncPreviewView({ target }) {
    const url = new URL(target.contentWindow.location.href);
    previewEvent.value = url.searchParams.get("event");
}

watch(snippet, () => copied.value = false);

async function copyEmbed() {
    try {
        await navigator.clipboard.writeText(snippet.value);
        copied.value = true;
    } catch {
        embedCode.value?.focus();
        embedCode.value?.select();
    }
}

function disableWidget() {
    if (window.confirm("Disable this widget and revoke its embed link?")) {
        disableForm.delete(route("events.widget.disable"), { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout title="Website widget">
        <h1 class="mb-2 text-3xl font-semibold">Website widget</h1>
        <p class="mb-6 max-w-2xl text-sm text-slate-600">Show up to 12 published occurrences from the next 60 days. Anyone with the embed link can see their titles, times, locations and cover images.</p>

        <div class="grid items-start gap-6 xl:grid-cols-2">
            <div class="space-y-6">
                <form class="workspace-panel space-y-4 p-5 sm:p-6" @submit.prevent="form.put(route('events.widget.save'), { preserveScroll: true })">
                    <h2 class="text-lg font-semibold">Widget settings</h2>
                    <div>
                        <label for="widget-title" class="workspace-label">Title</label>
                        <input id="widget-title" v-model="form.title" class="workspace-input" maxlength="100" required />
                    </div>
                    <div>
                        <label for="widget-source" class="workspace-label">Event source</label>
                        <select id="widget-source" v-model="form.source" class="workspace-input">
                            <option value="">All sources</option>
                            <option v-for="source in sources" :key="source" :value="source">{{ sourceNames[source] ?? source }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="widget-template" class="workspace-label">Event template</label>
                        <select id="widget-template" v-model="form.template_id" class="workspace-input">
                            <option value="">All templates</option>
                            <option v-if="form.template_id && !templates.some(template => template.id === form.template_id)" :value="form.template_id" disabled>Deleted template</option>
                            <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</option>
                        </select>
                    </div>
                    <fieldset class="space-y-4 rounded-lg border border-slate-200 p-4">
                        <legend class="px-1 text-sm font-semibold">Appearance and branding</legend>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="widget-theme" class="workspace-label">Colour theme</label>
                                <select id="widget-theme" v-model="form.appearance.theme" class="workspace-input">
                                    <option v-for="(label, key) in themes" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="widget-brand" class="workspace-label">Organisation name</label>
                                <input id="widget-brand" v-model="form.appearance.brand" class="workspace-input" maxlength="70" placeholder="Optional" />
                            </div>
                        </div>
                        <div class="grid gap-3 text-sm">
                            <label class="flex items-center gap-2"><input v-model="form.appearance.compact" type="checkbox" /> Use compact spacing</label>
                            <label class="flex items-center gap-2"><input v-model="form.appearance.show_credit" type="checkbox" /> Show EventJunction credit</label>
                            <div>
                                <label class="flex items-center gap-2">
                                    <input v-model="form.appearance.show_description" type="checkbox" aria-describedby="widget-description-help" />
                                    Show event descriptions on detail pages
                                </label>
                                <p id="widget-description-help" class="mt-1 pl-5 text-xs text-slate-600">Descriptions from published events will be visible to anyone with the embed link.</p>
                            </div>
                        </div>
                    </fieldset>
                    <p v-for="(error, field) in form.errors" :key="field" class="text-sm text-red-700" role="alert">{{ error }}</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <button class="workspace-primary" :disabled="form.processing || disableForm.processing">{{ widget ? "Save settings" : "Enable widget" }}</button>
                        <button v-if="widget" type="button" class="workspace-secondary text-red-700" :disabled="form.processing || disableForm.processing" @click="disableWidget">Disable widget</button>
                    </div>
                </form>

                <section v-if="embedUrl" class="workspace-panel space-y-3 p-5 sm:p-6" aria-labelledby="embed-code-heading">
                    <h2 id="embed-code-heading" class="text-lg font-semibold">Embed code</h2>
                    <p class="text-sm text-slate-600">Paste this into an HTML block on your website.</p>
                    <textarea ref="embedCode" :value="snippet" readonly aria-label="Embed code" rows="4" class="workspace-input w-full font-mono text-xs"></textarea>
                    <button type="button" class="workspace-secondary" aria-live="polite" @click="copyEmbed">{{ copied ? "Copied" : "Copy embed code" }}</button>
                </section>
            </div>

            <section class="workspace-panel overflow-hidden xl:sticky xl:top-5" aria-labelledby="widget-preview-heading">
                <div class="space-y-3 border-b px-5 py-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="widget-preview-heading" class="font-semibold">Live preview</h2>
                        <a v-if="embedUrl" :href="embedUrl" target="_blank" rel="noopener" class="text-sm font-medium text-emerald-800 underline">Open published widget</a>
                    </div>
                    <p class="text-sm text-slate-600">Try your changes with sample events. Save to update your embed.</p>
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Widget preview view">
                        <button type="button" class="workspace-secondary aria-pressed:border-emerald-700 aria-pressed:bg-emerald-50" :aria-pressed="!previewEvent" @click="showPreview()">Agenda</button>
                        <button type="button" class="workspace-secondary aria-pressed:border-emerald-700 aria-pressed:bg-emerald-50" :aria-pressed="!!previewEvent" @click="showPreview('garden-workshop')">Event detail</button>
                    </div>
                </div>
                <iframe :key="previewVersion" :src="preview" title="Upcoming events widget preview" class="block h-[460px] w-full border-0" @load="syncPreviewView"></iframe>
            </section>
        </div>
    </AppLayout>
</template>
