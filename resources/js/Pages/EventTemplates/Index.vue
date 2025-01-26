<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import {Link} from '@inertiajs/vue3'
import {defineProps, ref} from 'vue'
import Mutate from './Mutate.vue'
import Modal from "@/Components/Modal.vue"

const props = defineProps({
    eventTemplates: Array
})

const showEventTemplateModal = ref(false)
const selectedEventTemplate = ref(null)

const editEventTemplate = (template) => {
    selectedEventTemplate.value = template
    showEventTemplateModal.value = true
}

const createEventTemplate = () => {
    selectedEventTemplate.value = null
    showEventTemplateModal.value = true
}

const close = () => {
    selectedEventTemplate.value = null
    showEventTemplateModal.value = false
}

const deleteEventTemplate = (id) => {
    if(confirm('Are you sure you want to delete this event template?')) {
        // Implement deletion logic
        this.$inertia.delete(route('event-templates.destroy', id))
    }
}
</script>

<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Event Templates</h2>
                    <button
                        @click="createEventTemplate"
                        class="bg-blue-500 text-white px-4 py-2 rounded-md"
                    >
                        Create Event Template
                    </button>

                    <Modal
                        :show="showEventTemplateModal"
                        @close="showEventTemplateModal = false"
                        :max-width="'7xl'"
                    >
                        <Mutate
                            @created="showEventTemplateModal = false"
                            :event-template="selectedEventTemplate"
                            @submit="close"
                        />
                    </Modal>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="template in eventTemplates"
                        :key="template.id"
                        class="flex justify-between items-center p-3 bg-gray-50 rounded"
                    >
                        <div>
                            <span class="font-medium">{{ template.name }}</span>
                            <span class="ml-2 text-sm text-gray-500">
                                {{ template.field_configurations.length }} Fields
                            </span>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="editEventTemplate(template)"
                                class="text-blue-500"
                            >
                                Edit
                            </button>
                            <button
                                @click="deleteEventTemplate(template.id)"
                                class="text-red-500"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
