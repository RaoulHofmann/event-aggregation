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
    />
</template>
