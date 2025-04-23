<script setup>
import { useForm } from '@inertiajs/vue3'
import {ref, computed, onMounted, defineProps} from "vue"

const EVENT_TEMPLATES_API = 'api/event-templates'
const FIELD_TEMPLATES_API = 'api/field-templates'

const emit = defineEmits(['submit'])
const props = defineProps({event: Object})

const editMode = ref(!!props.event)
const templates = ref([])
const customFields = ref([])
const loading = ref(true)

const form = useForm( props.event ?? {
    template_id: '',
    event_data: {},
})

const fetchData = async (apiUrl) => (await axios.get(apiUrl)).data;


onMounted(async () => {
    try {
        templates.value = await fetchData(EVENT_TEMPLATES_API)
        customFields.value = await fetchData(FIELD_TEMPLATES_API)

        if (props.event) {
            form.template_id = props.event.template_id || '' // Fallback value
            form.event_data = props.event.event_data || {}
        }
    } catch (error) {
        console.error('Failed to fetch data:', error)
    }
    loading.value = false
})

const findTemplateById = (id) => templates.value.find(template => template.id === id)
const findCustomFieldById = (id) => customFields.value.find(field => field.id === id)

const getTemplateFieldsData = (fieldIds) =>
    fieldIds
        .map(id => findCustomFieldById(id))
        .filter(field => field)

// Computed properties for form fields and layout
const formFields = computed(() => {
    const selectedTemplate = findTemplateById(form.template_id)
    return selectedTemplate ? getTemplateFieldsData(selectedTemplate.fields) : []
})

const formLayout = computed(() => {
    const selectedTemplate = findTemplateById(form.template_id)
    return selectedTemplate?.layout || [[], []]
})

const handleTemplateChange = (newTemplateId) => {
    form.event_data = formFields.value.reduce((eventData, field) => {
        eventData[field.field_id] = field.default || ''
        return eventData
    }, {})
}

const submit = () => {
    const action = editMode.value ? 'put' : 'post'
    const routeName = editMode.value ?
        route('events.update', {event: form.id}) :
        route('events.store')

    form[action](routeName, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('submit')
        }
    })
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

                <div v-if="loading" class="flex items-center justify-center">
                    <span class="loader border-t-2 border-blue-500 rounded-full w-4 h-4 animate-spin"></span>
                    <span class="ml-2 text-sm text-gray-500">Loading...</span>
                </div>
                <template v-else>
                    <label for="template" class="block text-sm font-medium text-gray-700">Select Template</label>
                    <select  v-model="form.template_id" @change="handleTemplateChange"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Template</option>
                        <option v-for="template in templates" :key="template.id" :value="template.id">
                            {{ template.name }}
                        </option>
                    </select>
                </template>
            </div>

            <!-- Event Data Fields -->
            <div v-if="formFields.length" class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800">Event Data</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="(column, columnIndex) in formLayout" :key="columnIndex" class="space-y-4">
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
                                           :required="getField(fieldId).required ?? false"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </template>
                                <template v-else-if="getField(fieldId).type === 'textarea'">
                                    <textarea v-model="form.event_data[getField(fieldId).field_id]" rows="3"
                                              :required="getField(fieldId).required ?? false"
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                </template>
                                <template v-else-if="getField(fieldId).type === 'select'">
                                    <select v-model="form.event_data[getField(fieldId).field_id]" :multiple="getField(fieldId).multiple ?? false"
                                            :required="getField(fieldId).required ?? false"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option v-for="option in getField(fieldId).options" :key="option"
                                                :value="option">{{ option }}
                                        </option>
                                    </select>
                                </template>
                                <template v-else-if="getField(fieldId).type === 'boolean' || getField(fieldId).type === 'checkbox'">
                                    <div class="flex items-center">
                                        <input type="checkbox" v-model="form.event_data[getField(fieldId).field_id]"
                                               :required="getField(fieldId).required ?? false"
                                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <label class="ml-2 text-sm text-gray-700">{{ getField(fieldId).label }}</label>
                                    </div>
                                </template>
                                <template
                                    v-else-if="getField(fieldId).type === 'number' || getField(fieldId).type === 'decimal'">
                                    <input type="number" v-model="form.event_data[getField(fieldId).field_id]"
                                           :step="getField(fieldId).type === 'decimal' ? '0.01' : '1'"
                                           :required="getField(fieldId).required ?? false"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </template>
                                <template v-else-if="getField(fieldId).type === 'datetime'">
                                    <input type="datetime-local" v-model="form.event_data[getField(fieldId).field_id]"
                                           :required="getField(fieldId).required ?? false"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </template>
                                <template v-else-if="getField(fieldId).type === 'date'">
                                    <input type="date" v-model="form.event_data[getField(fieldId).field_id]"
                                           :required="getField(fieldId).required ?? false"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </template>
                                <template v-else-if="getField(fieldId).type === 'file'">
                                    <input type="file" v-on:change="form.event_data[getField(fieldId).field_id]"
                                           :accept="getField(fieldId)?.validation_rules[0] ?? 'png' "
                                           :required="getField(fieldId).required ?? false"
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
                <button :disabled="loading" type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md shadow-sm transition-colors">
                    {{ editMode ? 'Update' : 'Create' }} Event
                </button>
            </div>
        </form>
    </div>
</template>
