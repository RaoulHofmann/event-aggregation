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
const preview = computed(() => props.previewPath || route("widgets.upcoming.example", { theme: form.appearance.theme }, false));
const previewKey = computed(() => JSON.stringify([props.widget, props.appearance]));

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

            <section class="workspace-panel overflow-hidden" aria-labelledby="widget-preview-heading">
                <div class="border-b px-5 py-4">
                    <h2 id="widget-preview-heading" class="font-semibold">{{ widget ? "Published widget preview" : "Example widget preview" }}</h2>
                    <p v-if="widget && form.isDirty" class="mt-1 text-sm text-slate-600">Save to update the preview.</p>
                </div>
                <iframe :key="previewKey" :src="preview" title="Upcoming events widget preview" class="block h-[460px] w-full border-0"></iframe>
            </section>
        </div>
    </AppLayout>
</template>
