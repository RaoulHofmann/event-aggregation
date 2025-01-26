<script setup>
import {ref, onMounted, defineProps} from 'vue'
import { useForm } from '@inertiajs/vue3'

const emit = defineEmits(['submit'])

const props = defineProps({
    eventTemplate: Object,
})

const form = useForm(props.eventTemplate ?? {
    name: '',
    description: '',
    field_configurations: []
})

const availableFields = ref([])
const selectedFields = ref(props.eventTemplate?.field_configurations?.map(f => ({ id: f })) ?? [])
const editMode = ref(props.eventTemplate !== null)

onMounted(async () => {
    // Fetch available field templates
    const response = await axios.get(route('event-templates.data'))
    availableFields.value = response.data
})

const toggleFieldSelection = (field) => {
    const index = selectedFields.value.findIndex(f => f.id === field.id)
    if (index > -1) {
        selectedFields.value.splice(index, 1)
    } else {
        selectedFields.value.push(field)
    }
}

const mutateEventTemplate = () => {
    form.field_configurations = selectedFields.value.map(field => field.id)

    const action = editMode ? 'put' : 'post'
    const routeName = editMode ? route('event-templates.update', { eventTemplate: form.id }) : route('event-templates.store')

    form[action](routeName, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            selectedFields.value = []
        }
    })

    emit('submit')
}
</script>

<template>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <form @submit.prevent="mutateEventTemplate" class="space-y-6">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Template Name</label>
                        <input
                            v-model="form.name"
                            placeholder="Enter template name"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            required
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            v-model="form.description"
                            placeholder="Enter template description"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        ></textarea>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium mb-4">Select Fields</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div
                            v-for="field in availableFields"
                            :key="field.id"
                            @click="toggleFieldSelection(field)"
                            class="p-4 border rounded-lg cursor-pointer"
                            :class="{
                                'bg-blue-100': selectedFields.some(f => f.id === field.id)
                            }"
                        >
                            <div class="flex justify-between items-center">
                                <span>{{ field.label }}</span>
                                <span class="text-sm text-gray-500">{{ field.type }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded-md"
                >
                    {{ editMode ? 'Edit' : 'Save'}} Event Template
                </button>
            </form>
        </div>
    </div>
</template>
