<script setup>
import {defineProps, ref, watch} from 'vue'
import {useForm} from '@inertiajs/vue3'

const emit = defineEmits(['submit'])

const props = defineProps({
    fieldTemplate: Object,
})

const getDefaultValidationRules = (type) => {
    const defaults = {
        text: { max: 255, min: 0 },
        number: { min: 0, max: 999999 },
        decimal: { min: 0, max: 999999, precision: 2 },
        date: {},
        datetime: {},
        email: {},
        url: {},
        select: {},
        boolean: {},
        textarea: { max: 1000 }
    }
    return defaults[type] || {}
}

const form = useForm(props.fieldTemplate ?? {
    label: '',
    id: '',
    field_id: '',
    type: 'text',
    required: false,
    validation_rules: getDefaultValidationRules('text'),
    options: [],
})

const showOptionsInput = ref(false)
const optionInput = ref('')
const editMode = ref(props.fieldTemplate !== null)

const fieldTypes = [
    {value: 'text', label: 'Text'},
    {value: 'number', label: 'Number'},
    {value: 'decimal', label: 'Decimal'},
    {value: 'date', label: 'Date'},
    {value: 'datetime', label: 'DateTime'},
    {value: 'email', label: 'Email'},
    {value: 'url', label: 'URL'},
    {value: 'select', label: 'Select'},
    {value: 'boolean', label: 'Boolean'},
    {value: 'textarea', label: 'Text Area'}
]

watch(() => form.type, (newType) => {
    form.validation_rules = getDefaultValidationRules(newType)
    showOptionsInput.value = newType === 'select'
})

const addOption = () => {
    if (optionInput.value.trim()) {
        form.options.push(optionInput.value.trim())
        optionInput.value = ''
    }
}

const removeOption = (index) => {
    form.options.splice(index, 1)
}

const mutateFieldTemplate = () => {
    form.options = form.type === 'select' ? form.options : null

    const action = editMode.value ? 'put' : 'post'
    const routeName = editMode.value ?
        route('field-templates.update', {fieldTemplate: form.id}) :
        route('field-templates.store')

    form[action](routeName, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            showOptionsInput.value = false
            emit('submit')
        }
    })
}
</script>

<template>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <form @submit.prevent="mutateFieldTemplate" class="space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">Basic Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Field Label</label>
                            <input
                                v-model="form.label"
                                placeholder="Enter field label"
                                class="mt-1 block w-full rounded-md border-gray-300"
                                required
                            >
                            <p v-if="form.errors.label" class="text-red-500 text-xs mt-1">{{ form.errors.label }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Field ID</label>
                            <input
                                v-model="form.field_id"
                                placeholder="Enter field id"
                                class="mt-1 block w-full rounded-md border-gray-300"
                                required
                            >
                            <p v-if="form.errors.field_id" class="text-red-500 text-xs mt-1">{{
                                    form.errors.field_id
                                }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Field Type</label>
                            <select
                                v-model="form.type"
                                class="mt-1 block w-full rounded-md border-gray-300"
                                required
                            >
                                <option v-for="type in fieldTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                        </div>

                        <div class="flex items-center mt-4">
                            <input
                                type="checkbox"
                                v-model="form.required"
                                class="rounded border-gray-300"
                            >
                            <span class="ml-2 text-sm">Required Field</span>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">Validation Rules</h3>

                        <template v-if="['text', 'textarea'].includes(form.type)">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Maximum Length</label>
                                <input
                                    type="number"
                                    v-model="form.validation_rules.max"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                >
                            </div>
                        </template>

                        <template v-if="['number', 'decimal'].includes(form.type)">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Minimum Value</label>
                                    <input
                                        type="number"
                                        v-model="form.validation_rules.min"
                                        class="mt-1 block w-full rounded-md border-gray-300"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Maximum Value</label>
                                    <input
                                        type="number"
                                        v-model="form.validation_rules.max"
                                        class="mt-1 block w-full rounded-md border-gray-300"
                                    >
                                </div>
                            </div>
                            <div v-if="form.type === 'decimal'">
                                <label class="block text-sm font-medium text-gray-700">Decimal Places</label>
                                <input
                                    type="number"
                                    v-model="form.validation_rules.precision"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                    min="0"
                                    max="10"
                                >
                            </div>
                        </template>

                        <template v-if="form.type === 'select'">
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <input
                                        v-model="optionInput"
                                        placeholder="Enter option"
                                        class="flex-1 rounded-md border-gray-300"
                                    >
                                    <button
                                        type="button"
                                        @click="addOption"
                                        class="px-4 py-2 bg-green-500 text-white rounded-md"
                                    >
                                        Add
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div
                                        v-for="(option, index) in form.options"
                                        :key="index"
                                        class="bg-gray-100 px-3 py-1 rounded-full flex items-center gap-2"
                                    >
                                        <span>{{ option }}</span>
                                        <button
                                            type="button"
                                            @click="removeOption(index)"
                                            class="text-red-500"
                                        >
                                            ×
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button
                        type="submit"
                        class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600"
                    >
                        {{ editMode ? 'Update' : 'Create' }} Field Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
