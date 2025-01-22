<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {ref} from 'vue';

// Define props to accept data passed from the backend (Inertia)
const props = defineProps({
    events: Array,
    fields: Array
});

import Create from './Create.vue';
import Modal from "@/Components/Modal.vue";

const showEventModal = ref(false);
</script>

<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Events Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Events</h2>
                    <button @click="showEventModal = true" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                        Create Event
                    </button>

                    <Modal :show="showEventModal" @close="showEventModal = false" :max-width="'7xl'">
                        <Create @created="showEventModal = false" :custom-fields="fields" />
                    </Modal>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Name</th>
                            <th class="text-left py-2">Date</th>
                            <th class="text-left py-2">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="event in events" :key="event.id" class="border-b">
                            <td class="py-2">{{ event.name }}</td>
                            <td class="py-2">{{ new Date(event.start_date).toLocaleDateString() }}</td>
                            <td class="py-2">
                   <span :class="{
                     'px-2 py-1 rounded text-sm': true,
                     'bg-green-100 text-green-800': event.status === 'published',
                     'bg-yellow-100 text-yellow-800': event.status === 'draft'
                   }">
                     {{ event.status }}
                   </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
