<script setup>
import { Head } from "@inertiajs/vue3";
import NoticeboardHome from "@/Components/NoticeboardHome.vue";

defineProps({
    pageTitle: { type: String, required: true },
});
</script>
<template>
    <Head :title="pageTitle" />
    <NoticeboardHome />
</template>
