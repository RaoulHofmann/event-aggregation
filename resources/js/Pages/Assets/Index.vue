<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import WorkspaceImages from "@/Components/WorkspaceImages.vue";

defineProps({ canUpload: Boolean, canDelete: Boolean });
</script>

<template>
    <AppLayout title="Image library">
        <header class="mb-6">
            <h1 class="text-3xl font-semibold">Image library</h1>
            <p class="mt-2 text-sm text-slate-600">Upload images for cover photos across this workspace’s events.</p>
        </header>
        <section class="workspace-panel p-4 sm:p-6" aria-label="Workspace images">
            <WorkspaceImages :can-upload="canUpload" :can-delete="canDelete" />
        </section>
    </AppLayout>
</template>
