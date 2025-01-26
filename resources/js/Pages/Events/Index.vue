<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {ref} from 'vue';
import Modal from "@/Components/Modal.vue";
import Create from './Create.vue';
import Table from '@/Components/Table.vue';

const props = defineProps({
    events: Array,
    fields: Array
});

const showEventModal = ref(false);

const columns = [
    {field: 'name', header: 'Name'},
    {field: 'start_date', header: 'Date', format: 'date'},
    {field: 'status', header: 'Status'}
];
</script>

<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Events</h2>
                    <button @click="showEventModal = true" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                        Create Event
                    </button>
                </div>

                <Modal :show="showEventModal" @close="showEventModal = false" :max-width="'7xl'">
                    <Create @created="showEventModal = false" :custom-fields="fields" />
                </Modal>

                <Table :data="events?.map(event => ({ id: event.id, ...event?.event_data}))" :columns="columns" :paginate="true" :rows-per-page="10" />
            </div>
        </div>
    </AppLayout>
</template>
