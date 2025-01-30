<script setup>
import {ref, onMounted, defineProps, defineEmits} from 'vue'
import {useForm} from '@inertiajs/vue3'
import draggable from 'vuedraggable'

const emit = defineEmits(['submit'])
const props = defineProps({eventTemplate: Object})

const form = useForm(props.eventTemplate ?? {
    name: '',
    description: '',
    fields: [],
    layout: [[], [], []]
})

const availableFields = ref([])
const selectedFields = ref(props.eventTemplate?.fields ?? [])
const columns = ref(form.layout || [[], [], []])
const editMode = ref(!!props.eventTemplate)
const errors = ref({})

const validateForm = () => {
    errors.value = {}
    if (!form.name?.trim()) errors.value.name = 'Template name is required'
    if (!form.description?.trim()) errors.value.description = 'Description is required'
    if (!selectedFields.value.length) errors.value.fields = 'At least one field is required'
    return Object.keys(errors.value).length === 0
}

onMounted(async () => {
    const response = await axios.get(route('event-templates.data'))
    availableFields.value = response.data
})

const toggleFieldSelection = (fieldId, columnIndex) => {
    columns.value[columnIndex] = columns.value[columnIndex].filter(f => f !== fieldId)
    selectedFields.value = selectedFields.value.filter(f => f !== fieldId)
}

const addFieldToColumn = (field) => {
    if (!selectedFields.value.includes(field.id)) {
        columns.value[0].push(field.id)
        selectedFields.value.push(field.id)
        delete errors.value.fields
    }
}

const mutateEventTemplate = () => {
    if (!validateForm()) return
    form.fields = selectedFields.value
    form.layout = columns.value

    form[editMode.value ? 'put' : 'post'](
        route(editMode.value ? 'event-templates.update' : 'event-templates.store', {eventTemplate: form.id}),
        {
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
                selectedFields.value = []
                columns.value = [[], [], []]
                errors.value = {}
                emit('submit')
            },
            onError: (err) => (errors.value = err)
        }
    )
}

const getFieldDetails = (fieldId) => availableFields.value.find(f => f.id === fieldId)
</script>

<template>
    <div class="max-w-[90rem] mx-auto space-y-6">
        <form @submit.prevent="mutateEventTemplate" class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Template Name</label>
                    <input v-model="form.name" placeholder="Enter template name"
                           class="mt-1 block w-full rounded-md border-gray-300" :class="{'border-red-500': errors.name}"
                           required>
                    <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <input v-model="form.description" placeholder="Enter template description"
                           class="mt-1 block w-full rounded-md border-gray-300"
                           :class="{'border-red-500': errors.description}" required>
                    <p v-if="errors.description" class="mt-1 text-sm text-red-500">{{ errors.description }}</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <!-- Available Fields -->
                <div class="bg-white rounded-lg shadow p-4 w-auto">
                    <h3 class="text-lg font-medium mb-4">Available Fields</h3>
                    <p v-if="errors.fields" class="mb-2 text-sm text-red-500">{{ errors.fields }}</p>
                    <div class="overflow-y-auto h-[calc(100vh-20rem)] space-y-2 pr-2">
                        <div
                            v-for="field in availableFields"
                            :key="field.id"
                            class="p-3 border rounded-lg cursor-pointer transition-colors hover:bg-blue-50"
                            :class="{'bg-blue-50': selectedFields.includes(field.id)}"
                            @click="addFieldToColumn(field)"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ field.label }}</span>
                                <span class="text-xs text-gray-500 px-2 py-1 bg-gray-100 rounded">
                        {{ field.type.toUpperCase() }}
                    </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Draggable Columns -->
                <div class="col-span-2 grid grid-cols-2 gap-4">
                    <draggable
                        v-for="(column, columnIndex) in columns"
                        :key="columnIndex"
                        v-model="columns[columnIndex]"
                        group="fields"
                        item-key="id"
                        class="bg-gray-50 p-4 rounded-lg min-h-[calc(100vh-20rem)] border-2 border-dashed border-gray-200"
                    >
                        <template #item="{element}">
                            <div class="bg-white p-3 mb-2 rounded-lg shadow-sm border border-gray-200 cursor-move">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium">{{ getFieldDetails(element)?.label }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ getFieldDetails(element)?.type.toUpperCase() }}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="toggleFieldSelection(element, columnIndex)"
                                        class="text-gray-400 hover:text-red-500 transition-colors"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600">
                    {{ editMode ? 'Update' : 'Create' }} Template
                </button>
            </div>
        </form>
    </div>
</template>
