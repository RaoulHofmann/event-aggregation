<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted, watch } from "vue";

const templates = ref([]);
const customFields = ref([]);

const form = useForm({
    template_id: '',
    event_data: {},
});

onMounted(async () => {
    const response = await axios.get(route('events.create'));
    templates.value = response.data?.templates;
    customFields.value = response.data?.fields;
});

const formFields = reactive({
    fields: computed(() => {
        const selectedTemplate = templates.value.find(template => template.id === form.template_id);
        if (!selectedTemplate) return [];
        return selectedTemplate.field_configurations.map(fieldId =>
            customFields.value.find(field => field.id === fieldId)
        ).filter(field => field);
    })
});

watch(() => form.template_id, () => {
    loadTemplateFields();
});

const loadTemplateFields = () => {
    form.event_data = formFields.fields.reduce((acc, field) => {
        acc[field.fieldId] = field.default || '';
        return acc;
    }, {});
};

const submit = () => {
    form.post(route('events.store'));
};
</script>

<template>
    <div class="max-w-7xl mx-auto p-6 rounded-lg shadow bg-white">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Template Selection -->
            <div>
                <label for="template" class="block text-sm font-medium text-gray-700">Select Template</label>
                <select v-model="form.template_id" @change="loadTemplateFields"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select Template</option>
                    <option v-for="template in templates" :key="template.id" :value="template.id">
                        {{ template.name }}
                    </option>
                </select>
            </div>

            <!-- Event Data Fields -->
            <div v-if="formFields.fields.length" class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800">Event Data</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="field in formFields.fields" :key="field.fieldId" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">{{ field.label }}</label>

                        <!-- Handle all field types -->
                        <template v-if="field.type === 'text' || field.type === 'email' || field.type === 'url'">
                            <input :type="field.type" v-model="form.event_data[field.fieldId]"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </template>
                        <template v-else-if="field.type === 'textarea'">
                            <textarea v-model="form.event_data[field.fieldId]" rows="3"
                                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </template>
                        <template v-else-if="field.type === 'select'">
                            <select v-model="form.event_data[field.fieldId]"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option v-for="option in JSON.parse(field.options)" :key="option" :value="option">{{ option }}</option>
                            </select>
                        </template>
                        <template v-else-if="field.type === 'boolean'">
                            <div class="flex items-center">
                                <input type="checkbox" v-model="form.event_data[field.fieldId]"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm text-gray-700">{{ field.label }}</label>
                            </div>
                        </template>
                        <template v-else-if="field.type === 'number' || field.type === 'decimal'">
                            <input type="number" v-model="form.event_data[field.fieldId]" :step="field.type === 'decimal' ? '0.01' : '1'"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </template>
                        <template v-else-if="field.type === 'datetime'">
                            <input type="datetime-local" v-model="form.event_data[field.fieldId]"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </template>
                        <template v-else-if="field.type === 'date'">
                            <input type="date" v-model="form.event_data[field.fieldId]"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </template>
                        <template v-else>
                            <p class="text-red-500 text-sm">Unsupported field type: {{ field.type }}</p>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md shadow-sm transition-colors">
                    Create Event
                </button>
            </div>
        </form>
    </div>
</template>
