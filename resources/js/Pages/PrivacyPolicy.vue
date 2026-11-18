<script setup>
import LegalDocument from "@/Components/LegalDocument.vue";

defineProps({ policy: { type: String, required: true } });
</script>

<template>
    <LegalDocument title="Privacy Policy" :html="policy" />
</template>
