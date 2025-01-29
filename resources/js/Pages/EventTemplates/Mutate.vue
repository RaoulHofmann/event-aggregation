<script setup>
import {ref, onMounted, defineProps} from 'vue'
import {useForm} from '@inertiajs/vue3'
import draggable from 'vuedraggable'

const emit = defineEmits(['submit'])

const props = defineProps({
    eventTemplate: Object,
})

const form = useForm(props.eventTemplate ?? {
    name: '',
    description: '',
    fields: [],
    layout: [[], []]
})

const availableFields = ref([])
const selectedFields = ref(props.eventTemplate?.fields ?? [])
const columns = ref(form.layout || [[], []])
const editMode = ref(props.eventTemplate !== null)

const errors = ref({
    name: '',
    description: ''
})

onMounted(async () => {
    const response = await axios.get(route('event-templates.data'))
    availableFields.value = response.data
})

const toggleFieldSelection = (field, columnIndex) => {
    columns.value[columnIndex] = columns.value[columnIndex].filter(f => f !== field)
    const index = selectedFields.value.findIndex(f => f === field)
    if (index > -1) {
        selectedFields.value.splice(index, 1)
    }
}

const addFieldToColumn = (field) => {
    if (!columns.value[0].some(f => f === field.id)) {
        columns.value[0].push(field.id)
        selectedFields.value.push(field.id)
    }
}

const validateForm = () => {
    let isValid = true
    if (!form.name) {
        errors.value.name = 'Template name is required'
        isValid = false
    }
    if (!form.description) {
        errors.value.description = 'Description is required'
        isValid = false
    }
    return isValid
}

const mutateEventTemplate = () => {
    if (!validateForm()) return

    form.fields = selectedFields.value
    form.layout = columns.value

    const action = editMode.value ? 'put' : 'post'
    const routeName = editMode.value ? route('event-templates.update', {eventTemplate: form.id}) : route('event-templates.store')

    form[action](routeName, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            selectedFields.value = []
            columns.value = [[], []]
        }
    })

    emit('submit')
}

const getFieldDetails = (fieldId) => {
    return availableFields.value.find(f => f.id === fieldId)
}
</script>

<template>
    <div class="flex gap-6">
        <!-- Main Form Area -->
        <div class="flex-auto max-w-6xl">
            <div class="bg-white p-6 rounded-lg shadow">
                <form @submit.prevent="mutateEventTemplate" class="space-y-6">
                    <!-- Template Name and Description -->
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Template Name</label>
                            <input
                                v-model="form.name"
                                placeholder="Enter template name"
                                class="mt-1 block w-full rounded-md border-gray-300"
                                required
                            >
                            <span v-if="errors.name" class="text-red-500 text-sm">{{ errors.name }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                v-model="form.description"
                                placeholder="Enter template description"
                                class="mt-1 block w-full rounded-md border-gray-300"
                            ></textarea>
                            <span v-if="errors.description" class="text-red-500 text-sm">{{ errors.description }}</span>
                        </div>
                    </div>

                    <!-- Grid Layout -->
                    <div>
                        <h3 class="text-lg font-medium mb-4">Grid Layout</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <draggable
                                v-for="(column, columnIndex) in columns"
                                :key="columnIndex"
                                v-model="columns[columnIndex]"
                                group="fields"
                                item-key="id"
                                class="bg-gray-100 p-4 rounded-lg min-h-[200px]"
                            >
                                <template #item="{element}">
                                    <div
                                        class="p-4 mb-2 border rounded-lg bg-white cursor-move flex justify-between items-center"
                                    >
                                        <div>
                                            <span>{{
                                                    getFieldDetails(element)?.label || element.id
                                                }}</span>
                                            <span class="text-sm text-gray-500 ml-2">
                                                {{
                                                    getFieldDetails(element)?.type || 'Unknown'
                                                }}
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="toggleFieldSelection(element, columnIndex)"
                                            class="text-red-500 hover:text-red-700 ml-2"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </template>
                            </draggable>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-md"
                    >
                        {{ editMode ? 'Edit' : 'Save' }} Event Template
                    </button>
                </form>
            </div>
        </div>

        <!-- Available Fields Sidebar -->
        <div class="flex-auto max-w-xl mt-5 bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium mb-4">Available Fields</h3>
            <div class="overflow-y-auto max-h-[calc(100vh-3rem)] space-y-2">
                <div
                    v-for="field in availableFields"
                    :key="field.id"
                    class="p-3 border rounded-lg cursor-pointer hover:bg-blue-50"
                    :class="{
                        'bg-blue-100': selectedFields.some(f => f === field.id)
                    }"
                    @click="addFieldToColumn(field)"
                >
                    <div class="flex items-center">
                        <span>{{ field.label }}</span>
                        <span class="text-sm text-gray-500 ml-2">{{ field.type }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
