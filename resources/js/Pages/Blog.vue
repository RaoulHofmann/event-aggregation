<script setup>
import { Head } from "@inertiajs/vue3";
import PublicSiteHeader from "@/Components/PublicSiteHeader.vue";

defineProps({ title: String, blogHtml: String });
</script>

<template>
    <Head v-if="title" :title="title" />
    <PublicSiteHeader />
    <div v-if="blogHtml" v-html="blogHtml" />
</template>
