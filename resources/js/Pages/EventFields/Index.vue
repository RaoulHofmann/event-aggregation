<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Link} from '@inertiajs/vue3';
import {defineProps, ref} from 'vue';

// Define props to accept data passed from the backend (Inertia)
const props = defineProps({
    events: Array,
    fields: Array
});

import Create from './Create.vue';
import Modal from "@/Components/Modal.vue";

const showEventFieldsModal = ref(false);
</script>

<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Fields Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Custom Fields</h2>
                    <button @click="showEventFieldsModal = true" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                        Create Field
                    </button>

                    <Modal :show="showEventFieldsModal" @close="showEventFieldsModal = false" :max-width="'7xl'">
                        <Create @created="showEventFieldsModal = false" :custom-fields="fields" />
                    </Modal>
                </div>

                <div class="space-y-2">
                    <div v-for="field in fields" :key="field.id"
                         class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <div>
                            <span class="font-medium">{{ field.name }}</span>
                            <span class="ml-2 text-sm text-gray-500">{{ field.type }}</span>
                            <span v-if="field.required" class="ml-2 text-xs text-red-500">Required</span>
                        </div>
                        <div class="flex space-x-2">
                            <button class="text-blue-500">Edit</button>
                            <button class="text-red-500">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
