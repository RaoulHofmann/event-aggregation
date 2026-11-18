<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AuthenticationCard from "@/Components/AuthenticationCard.vue";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";

defineProps({ status: { type: String, required: true } });

const messages = {
    pending: ["Registration awaiting approval", "Your request has been received. Visit this page again or sign in later to check for approval."],
    rejected: ["Registration declined", "Contact your EventJunction administrator if you think this decision was made in error."],
    suspended: ["Account suspended", "Contact your EventJunction administrator to restore access."],
};
</script>

<template>
    <Head title="Account status" />
    <AuthenticationCard>
        <template #logo><AuthenticationCardLogo /></template>
        <h1 class="text-xl font-semibold text-slate-950">{{ messages[status]?.[0] ?? "Access restricted" }}</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">{{ messages[status]?.[1] }}</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <Link :href="route('account.status')" class="workspace-primary">Check status</Link>
            <Link :href="route('logout')" method="post" as="button" class="workspace-secondary">Sign out</Link>
        </div>
    </AuthenticationCard>
</template>
