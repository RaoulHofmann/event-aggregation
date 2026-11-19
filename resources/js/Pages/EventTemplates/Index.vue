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
    />
</template>
