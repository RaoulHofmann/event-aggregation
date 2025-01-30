<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'
import Modal from "@/Components/Modal.vue"
import Create from './Create.vue'
import Table from '@/Components/Table.vue'

const props = defineProps({
    events: Array,
    eventTemplates: Array,
    fieldTemplates: Array,
})

const showEventModal = ref(false)

const groupedEvents = computed(() => {
    const groups = {}
    props.events.forEach(event => {
        const templateId = event.template_id
        if (!groups[templateId]) {
            const template = props.eventTemplates.find(t => t.id === templateId)
            if (template) {
                groups[templateId] = {
                    template: template,
                    events: []
                }
            }
        }
        if (groups[templateId]) {
            groups[templateId].events.push(event)
        }
    })
    return groups
})

const getColumnsForTemplate = (template, events) => {
    if (!template || !template.fields) return []

    // Get all field IDs that have data in at least one event
    const fieldsWithData = new Set()
    events.forEach(event => {
        Object.keys(event.event_data).forEach(field => {
            if (event.event_data[field] !== null && event.event_data[field] !== undefined) {
                fieldsWithData.add(field)
            }
        })
    })

    return template.fields
        .map(fieldId => {
            const fieldTemplate = props.fieldTemplates.find(f => f.id === fieldId)
            if (fieldTemplate && fieldsWithData.has(fieldTemplate.field_id)) {
                return {
                    field: fieldTemplate.field_id,
                    header: fieldTemplate.label,
                    format: fieldTemplate.type === 'date' ? 'date' : 'text'
                }
            }
            return null
        })
        .filter(Boolean)
}
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
                    <Create @submit="showEventModal = false" :custom-fields="fieldTemplates"/>
                </Modal>
                <div v-for="(group, templateId) in groupedEvents" :key="templateId" class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">{{ group.template.name }}</h3>
                    <Table
                        :data="group.events.map(event => ({ id: event.id, ...event.event_data }))"
                        :columns="getColumnsForTemplate(group.template, group.events)"
                        :paginate="true"
                        :rows-per-page="10"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
