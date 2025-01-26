<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, reactive, computed, onMounted, watch } from "vue"

const templates = ref([])
const customFields = ref([])

const form = useForm({
    template_id: '',
    event_data: {},
    edit: false
})

onMounted(async () => {
    const response = await axios.get(route('events.data'))
    templates.value = response.data?.templates
    customFields.value = response.data?.fields
})

const formFields = reactive({
    fields: computed(() => {
        const selectedTemplate = templates.value.find(template => template.id === form.template_id)
        if (!selectedTemplate) return []
        return selectedTemplate.fields.map(field_id =>
            customFields.value.find(field => field.id === field_id)
        ).filter(field => field)
    }),
    layout: computed(() => {
        const selectedTemplate = templates.value.find(template => template.id === form.template_id)
        console.log(selectedTemplate)

        return selectedTemplate?.layout || [[], []]
    })
})


watch(() => form.template_id, () => {
    loadTemplateFields()

    console.log(formFields.layout)

})

const loadTemplateFields = () => {
    form.event_data = formFields.fields.reduce((acc, field) => {
        acc[field.field_id] = field.default || ''
        return acc
    }, {})
}

const submit = () => {
    form.post(route('events.store'))
}

const getField = (fieldId) => {
    return customFields.value.find(field => field.id === fieldId)
}
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
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="(column, columnIndex) in formFields.layout" :key="columnIndex" class="space-y-4">
                        <div v-for="fieldId in column" :key="fieldId" class="space-y-2">
                            <template v-if="getField(fieldId)">
                                <label class="block text-sm font-medium text-gray-700">{{
                                        getField(fieldId).label
                                    }}</label>

                                <!-- Handle all field types -->
                                <template
                                    v-if="getField(fieldId).type === 'text' || getField(fieldId).type === 'email' || getField(fieldId).type === 'url'">
                                    <input :type="getField(fieldId).type"
                                           v-model="form.event_data[getField(fieldId).field_id]"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </template>
                                <template v-else-if="getField(fieldId).type === 'textarea'">
                                    <textarea v-model="form.event_data[getField(fieldId).field_id]" rows="3"
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                </template>
                                <template v-else-if="getField(fieldId).type === 'select'">
                                    <select v-model="form.event_data[getField(fieldId).field_id]"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option v-for="option in getField(fieldId).options" :key="option"
                                                :value="option">{{ option }}
                                        </option>
                                    </select>
                                </template>
                                <template v-else-if="getField(fieldId).type === 'boolean'">
                                    <div class="flex items-center">
                                        <input type="checkbox" v-model="form.event_data[getField(fieldId).field_id]"
                                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <label class="ml-2 text-sm text-gray-700">{{ getField(fieldId).label }}</label>
                                    </div>
                                </template>
                                <template
                                    v-else-if="getField(fieldId).type === 'number' || getField(fieldId).type === 'decimal'">
                                    <input type="number" v-model="form.event_data[getField(fieldId).field_id]"
                                           :step="getField(fieldId).type === 'decimal' ? '0.01' : '1'"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </template>
                                <template v-else-if="getField(fieldId).type === 'datetime'">
                                    <input type="datetime-local" v-model="form.event_data[getField(fieldId).field_id]"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </template>
                                <template v-else-if="getField(fieldId).type === 'date'">
                                    <input type="date" v-model="form.event_data[getField(fieldId).field_id]"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </template>
                                <template v-else>
                                    <p class="text-red-500 text-sm">Unsupported field type: {{
                                            getField(fieldId).type
                                        }}</p>
                                </template>
                            </template>
                        </div>
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
