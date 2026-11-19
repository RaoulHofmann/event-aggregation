<script setup>
import { computed, ref, watch } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    widget: Object,
    previewPath: String,
    embedUrl: String,
    templates: Array,
    sources: Array,
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
});
const disableForm = useForm({});
const copied = ref(false);
const embedCode = ref(null);
const snippet = computed(() => props.embedUrl
    ? `<iframe src="${props.embedUrl}" title="Upcoming events" loading="lazy" width="100%" height="420" style="border:0"></iframe>`
    : "");

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
        <Link :href="route('events.index')" class="text-sm font-medium text-emerald-800 underline">Back to events</Link>
        <h1 class="mb-2 mt-4 text-3xl font-semibold">Website widget</h1>
        <p class="mb-6 max-w-2xl text-sm text-slate-600">Show up to 12 published occurrences from the next 60 days. Anyone with the embed link can see their titles, times, and locations.</p>

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

            <section v-if="previewPath" class="workspace-panel overflow-hidden" aria-labelledby="widget-preview-heading">
                <h2 id="widget-preview-heading" class="border-b px-5 py-4 font-semibold">Preview</h2>
                <iframe :key="widget.title + widget.source + widget.template_id" :src="previewPath" title="Upcoming events widget preview" class="block h-[420px] w-full border-0"></iframe>
            </section>
        </div>
    </AppLayout>
</template>
