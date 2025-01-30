<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import {Link} from '@inertiajs/vue3'
import {defineProps, ref} from 'vue'
import Mutate from './Mutate.vue'
import Modal from "@/Components/Modal.vue"

const props = defineProps({
    fieldTemplates: Object
})

const showFieldTemplateModal = ref(false)
const selectedFieldTemplate = ref(null)

const editFieldTemplate = (field) => {
    selectedFieldTemplate.value = field
    showFieldTemplateModal.value = true
}

const newFieldTemplate = () => {
    selectedFieldTemplate.value = null
    showFieldTemplateModal.value = true
}

const close = () => {
    selectedFieldTemplate.value = null
    showFieldTemplateModal.value = false
}

const deleteFieldTemplate = (id) => {
    if(confirm('Are you sure you want to delete this field template?')) {
        // Implement deletion logic
        this.$inertia.delete(route('field-templates.destroy', id))
    }
}
</script>

<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Field Templates</h2>
                    <button
                        @click="newFieldTemplate"
                        class="bg-blue-500 text-white px-4 py-2 rounded-md"
                    >
                        Create Field Template
                    </button>

                    <Modal
                        :show="showFieldTemplateModal"
                        @close="showFieldTemplateModal = false"
                        :max-width="'7xl'"
                    >
                        <Mutate
                            @created="showFieldTemplateModal = false"
                            :field-template="selectedFieldTemplate"
                            @submit="close()"
                        />
                    </Modal>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="field in fieldTemplates"
                        :key="field.id"
                        class="flex justify-between items-center p-3 bg-gray-50 rounded"
                    >
                        <div>
                            <span class="font-medium">{{ field.label }}</span>
                            <span class="ml-2 text-sm text-gray-500">{{ field.type.toUpperCase() }}</span>
                            <span v-if="field.required" class="ml-2 text-xs text-red-500">Required</span>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="editFieldTemplate(field)"
                                class="text-blue-500"
                            >
                                Edit
                            </button>
                            <button
                                @click="deleteFieldTemplate(field.id)"
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
