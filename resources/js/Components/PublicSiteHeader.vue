<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import EventJunctionMark from "@/Components/EventJunctionMark.vue";

const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth?.user));
const currentPath = computed(() => page.url.split(/[?#]/, 1)[0]);
const links = [
    { label: "Templates", href: "/#templates" },
    { label: "Integrations", href: "/#integrations" },
    { label: "Workspaces", href: "/#workspaces" },
    { label: "Widget", href: "/#website-widget" },
    { label: "API docs", href: "/docs/api" },
    { label: "Pricing", href: "/pricing" },
];
</script>

<template>
    <header class="public-header">
        <div class="header-inner">
            <a href="/" class="wordmark" aria-label="EventJunction home" :aria-current="currentPath === '/' ? 'page' : undefined">
                <EventJunctionMark class="size-8 shrink-0" />EventJunction
            </a>
            <nav aria-label="Main navigation">
                <a v-for="link in links" :key="link.href" :href="link.href" :aria-current="currentPath === link.href ? 'page' : undefined">{{ link.label }}</a>
                <a :href="signedIn ? '/dashboard' : '/login'">{{ signedIn ? "Dashboard" : "Sign in" }}</a>
            </nav>
        </div>
    </header>
</template>

<style scoped>
.public-header {
    background: #fff0dc;
    color: #29251f;
    font: 16px/1.7 Arial, Helvetica, sans-serif;
}
.header-inner {
    width: min(1280px, calc(100% - 64px));
    margin-inline: auto;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 15px 24px;
    padding-block: 25px;
    border-bottom: 1px solid #cdbb9f;
}
.wordmark {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -1.6px;
    white-space: nowrap;
}
nav { display: flex; flex-wrap: wrap; gap: 8px clamp(16px, 2vw, 28px); font-weight: 600; }
a { color: inherit; text-decoration: none; }
nav a:hover, nav a[aria-current="page"] { text-decoration: underline; text-underline-offset: 4px; }
a:focus-visible { outline: 2px solid #9b5f3f; outline-offset: 4px; }
@media (max-width: 640px) {
    .header-inner { width: calc(100% - 48px); padding-block: 20px; }
    .wordmark { font-size: 28px; }
    nav { font-size: 14px; }
}
</style>
