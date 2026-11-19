<script setup>
import ApiTokenManager from '@/Pages/API/Partials/ApiTokenManager.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    tokens: Array,
    availablePermissions: Array,
    workspaceName: String,
});
</script>

<template>
    <AppLayout title="API Access">
        <h1 class="mb-6 text-3xl font-semibold">API Access</h1>
        <div>
            <div class="max-w-4xl">
                <ApiTokenManager
                    :tokens="tokens"
                    :available-permissions="availablePermissions"
                    :workspace-name="workspaceName"
                />
            </div>
        </div>
    </AppLayout>
</template>
