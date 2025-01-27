<script setup>
import {defineProps, ref} from 'vue'
import {useForm} from '@inertiajs/vue3'

const emit = defineEmits(['submit'])

const props = defineProps({
    fieldTemplate: Object,
})

const form = useForm(props.fieldTemplate ?? {
    label: '',
    id: '',
    field_id: '',
    type: 'text',
    required: false,
    validation_rules: {},
    options: [],
})

const showOptionsInput = ref(false)
const optionInput = ref('')
const validationRules = ref({})
const editMode = ref(props.fieldTemplate !== null)

const fieldTypes = [
    'text', 'number', 'date', 'datetime',
    'email', 'url', 'select', 'boolean',
    'textarea', 'decimal'
]

const addOption = () => {
    if (optionInput.value.trim()) {
        form.options.push(optionInput.value.trim())
        optionInput.value = ''
    }
}

const removeOption = (index) => {
    form.options.splice(index, 1)
}

const addValidationRule = (rule, value) => {
    form.validation_rules[rule] = value
}

const mutateFieldTemplate = () => {
    // Prepare validation rules and options
    form.validation_rules = Object.keys(validationRules.value).length
        ? validationRules.value
        : null

    form.options = form.type === 'select'
        ? form.options
        : null

    const action = editMode.value ? 'put' : 'post'
    const routeName = editMode.value ? route('field-templates.update', {fieldTemplate: form.id}) : route('field-templates.store')

    form[action](routeName, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            showOptionsInput.value = false
            validationRules.value = {}
            emit('submit')
        }
    })
}
</script>

<template>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <form @submit.prevent="mutateFieldTemplate" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Field Label -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Field Label</label>
                        <input
                            v-model="form.label"
                            placeholder="Enter field Label"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            required
                        >
                        <p v-if="form.errors.label" class="text-red-500 text-xs">{{ form.errors.label }}</p>
                    </div>

                    <!-- Field ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Field ID</label>
                        <input
                            v-model="form.field_id"
                            placeholder="Enter field id"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            required
                        >
                        <p v-if="form.errors.field_id" class="text-red-500 text-xs">{{ form.errors.field_id }}</p>
                    </div>

                    <!-- Field Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Field Type</label>
                        <select
                            v-model="form.type"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            @change="showOptionsInput = form.type === 'select'"
                            required
                        >
                            <option v-for="type in fieldTypes" :value="type">
                                {{ type }}
                            </option>
                        </select>
                        <p v-if="form.errors.type" class="text-red-500 text-xs">{{ form.errors.type }}</p>
                    </div>

                    <!-- Required Checkbox -->
                    <div class="flex items-end">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.required"
                                class="rounded border-gray-300"
                            >
                            <span class="ml-2">Required Field</span>
                        </label>
                    </div>
                </div>

                <!-- Validation Rules -->
                <div class="space-y-4">
                    <div v-if="form.type === 'text'">
                        <label>Max Length</label>
                        <input
                            type="number"
                            @change="addValidationRule('max', $event.target.value)"
                            class="rounded-md border-gray-300"
                        >
                    </div>
                    <div v-if="form.type === 'number'">
                        <label>Min Value</label>
                        <input
                            type="number"
                            @change="addValidationRule('min', $event.target.value)"
                            class="rounded-md border-gray-300"
                        >
                    </div>
                </div>

                <!-- Options for Select -->
                <div v-if="form.type === 'select'" class="space-y-4">
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
                            Add Option
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

                <button
                    type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded-md"
                >
                    {{ editMode ? 'Edit' : 'Save' }} Field Template
                </button>
            </form>
        </div>
    </div>
</template>
