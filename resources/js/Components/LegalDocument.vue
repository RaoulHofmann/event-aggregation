<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import EventJunctionMark from "@/Components/EventJunctionMark.vue";

defineProps({
    title: { type: String, required: true },
    html: { type: String, required: true },
});

const page = usePage();
const details = computed(() => page.props.legal);
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-[#fff7ed] px-5 py-8 text-slate-900 sm:px-8">
        <header class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-5 border-b border-[#d9c9b3] pb-6">
            <a href="/" class="inline-flex items-center gap-3 text-xl font-bold tracking-tight">
                <EventJunctionMark class="size-8" />
                EventJunction
            </a>
            <nav aria-label="Legal navigation" class="flex flex-wrap gap-4 text-sm font-medium">
                <a :href="route('policy.show')" class="underline underline-offset-4" :aria-current="title === 'Privacy Policy' ? 'page' : undefined">Privacy</a>
                <a :href="route('terms.show')" class="underline underline-offset-4" :aria-current="title === 'Terms of Service' ? 'page' : undefined">Terms</a>
            </nav>
        </header>
        <main class="mx-auto mt-8 max-w-3xl rounded-2xl border border-slate-200 bg-white px-6 py-8 sm:p-10">
            <p class="mb-4 text-sm text-slate-600">Last updated {{ details.updated }}</p>
            <article class="prose prose-slate max-w-none prose-a:text-emerald-800" v-html="html" />
            <section class="prose prose-slate mt-8 max-w-none border-t border-slate-200 pt-6 prose-a:text-emerald-800">
                <h2>Contact</h2>
                <p v-if="details.email">For questions or requests, email <a class="break-all" :href="'mailto:' + details.email">{{ details.email }}</a>.</p>
                <p v-else>For questions or requests, contact the EventJunction administrator using your existing correspondence. If you’re using a shared workspace, its owner can also help direct your request.</p>
            </section>
        </main>
    </div>
</template>
