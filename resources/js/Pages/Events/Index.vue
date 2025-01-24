<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import Modal from "@/Components/Modal.vue";
import Create from './Create.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

// Define props to accept data passed from the backend (Inertia)
const props = defineProps({
    events: Array,
    fields: Array
});

console.log(props.events)

const showEventModal = ref(false);

// Columns for the data table
const columns = [
    { field: 'name', header: 'Name' },
    { field: 'start_date', header: 'Date', format: 'date' },
    { field: 'status', header: 'Status' }
];
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
                </div>

                <!-- Create Event Modal -->
                <Modal :show="showEventModal" @close="showEventModal = false" :max-width="'7xl'">
                    <Create @created="showEventModal = false" :custom-fields="fields" />
                </Modal>

                <!-- Data Table -->
                <DataTable :value="events" :paginator="true" :rows="10" responsive-layout="scroll">
                    <Column v-for="col in columns" :key="col.field" :field="col.field" :header="col.header">
                        <template #body="slotProps" >
                            <!-- Format Date Column -->
                            <span v-if="col?.field?.start_date">
                                {{ new Date(slotProps.data?.event_data?.start_date).toLocaleDateString() }}
                            </span>

                            <!-- Format Status Column -->
                            <span v-else-if="col?.field?.status" :class="{
                                'px-2 py-1 rounded text-sm': true,
                                'bg-green-100 text-green-800': slotProps.data?.event_data?.status === 'published',
                                'bg-yellow-100 text-yellow-800': slotProps.data?.event_data?.status === 'draft'
                            }">
                                {{ slotProps.data?.event_data?.status }}
                            </span>

                            <!-- Default Rendering -->
                            <span v-else>
                                {{ slotProps.data?.event_data[col.field] }}
                            </span>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Optional Custom Styling */
</style>
