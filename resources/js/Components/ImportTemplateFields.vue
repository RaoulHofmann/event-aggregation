<script setup>
import { computed } from "vue";

const props = defineProps({
    eventTemplates: { type: Array, required: true },
    fieldTemplates: { type: Array, required: true },
    sourceFields: { type: Object, default: () => ({}) },
    unavailableSources: { type: Array, default: () => [] },
    canCreateTemplate: Boolean,
});
const templateId = defineModel("templateId");
const mapping = defineModel("mapping");
const sources = computed(() => {
    const labels = {
        title: "Event title",
        description: "Description",
        starts_at: "Start date and time",
        ends_at: "End date and time",
        timezone: "Timezone",
        location: "Location",
        status: "Import status",
        ...Object.fromEntries(Object.entries(props.sourceFields).map(([key, field]) => [key, field.label])),
    };
    return Object.fromEntries(Object.entries(labels).filter(([key]) => !props.unavailableSources.includes(key)));
});
const starterFieldLabels = computed(() => Object.values(props.sourceFields).map((field) => field.label));
const fields = computed(() => {
    const template = props.eventTemplates.find((item) => item.id === Number(templateId.value));
    return props.fieldTemplates.filter((item) => template?.fields?.includes(item.id));
});
</script>

<template>
    <div class="space-y-4">
        <label class="block font-medium">
            Event template
            <select v-model="templateId" class="workspace-input mt-1 block w-full" @change="mapping = {}">
                <option value="">Basic event</option>
                <option v-if="canCreateTemplate" value="new">Create a template for this source</option>
                <option v-for="template in eventTemplates" :key="template.id" :value="template.id">{{ template.name }}</option>
            </select>
        </label>
        <p v-if="templateId === 'new'" class="text-sm text-slate-600">
            Your workspace will get an editable template with {{ starterFieldLabels.join(", ") }}.
            Values are filled in when the source provides them.
        </p>
        <fieldset v-if="fields.length" class="space-y-3">
            <legend class="mb-2 font-medium">Match custom fields</legend>
            <label v-for="field in fields" :key="field.id" class="block text-sm">
                {{ field.label }}
                <select
                    :value="mapping[field.field_id] ?? ''"
                    class="workspace-input mt-1 block w-full"
                    @change="mapping = { ...mapping, [field.field_id]: $event.target.value }"
                >
                    <option value="">Fill in later</option>
                    <option v-for="(label, key) in sources" :key="key" :value="key">{{ label }}</option>
                </select>
            </label>
        </fieldset>
    </div>
</template>
