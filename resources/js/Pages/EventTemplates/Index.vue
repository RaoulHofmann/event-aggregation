<script setup>
import WorkspaceCollection from '@/Components/WorkspaceCollection.vue';
defineProps({ eventTemplates: Array });
</script>
<template>
    <WorkspaceCollection
        title="Event templates"
        singular="template"
        resource="event-templates"
        :items="eventTemplates"
        description="Reusable structures for every kind of event. Choose your fields once and keep your event data consistent."
    />
</template>
