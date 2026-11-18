<script setup>
import LegalDocument from "@/Components/LegalDocument.vue";

defineProps({ terms: { type: String, required: true } });
</script>

<template>
    <LegalDocument title="Terms of Service" :html="terms" />
</template>
