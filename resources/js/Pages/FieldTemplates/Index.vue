<script setup>
import WorkspaceCollection from '@/Components/WorkspaceCollection.vue';
defineProps({ fieldTemplates: Array });
</script>
<template>
    <WorkspaceCollection
        title="Custom fields"
        singular="field"
        resource="field-templates"
        :items="fieldTemplates"
        fields
        description="Capture the details that make your events yours. Reuse fields across all your templates."
    />
</template>
