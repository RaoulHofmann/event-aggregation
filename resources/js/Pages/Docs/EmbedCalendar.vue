<script setup>
import { Head } from "@inertiajs/vue3";
import EventJunctionMark from "@/Components/EventJunctionMark.vue";
</script>

<template>
    <Head title="How to embed an event calendar — EventJunction" />
    <div class="min-h-screen bg-[#fffaf1] text-slate-900">
        <div class="mx-auto max-w-5xl px-5 pt-6 sm:px-8">
            <header class="flex flex-wrap items-center justify-between gap-4 border-b border-amber-900/20 pb-5">
                <a href="/" class="flex items-center gap-2 font-bold"><EventJunctionMark class="size-8" /> EventJunction</a>
                <nav class="flex gap-4 text-sm" aria-label="Main navigation">
                    <a href="/#website-widget" class="underline">Widget demo</a>
                    <a href="/docs/api" class="underline">API documentation</a>
                </nav>
            </header>
            <main class="pb-12 pt-8 sm:pt-14">
                <h1 class="max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">How to embed an event calendar on your website</h1>
                <p class="mt-4 max-w-2xl text-lg leading-8">Use the EventJunction widget to show upcoming events on a site that supports custom HTML. The widget updates when you publish, edit or unpublish events in your workspace.</p>

                <div class="mt-10 grid items-start gap-6 md:grid-cols-2">
                    <section class="space-y-4">
                        <h2 class="text-2xl font-semibold">Set up your calendar</h2>
                        <ol class="list-decimal space-y-3 pl-5 leading-7">
                            <li>Publish the events you want visitors to see and check their dates and time zones.</li>
                            <li>As the workspace owner, open <strong>Website widget</strong> from the sidebar. Choose a title, theme and optional filters for event source or template.</li>
                            <li>Enable the widget and copy its embed code. Paste it into your site’s custom HTML block and publish the page.</li>
                        </ol>
                        <p class="leading-7">The agenda shows up to 12 published occurrences from the next 60 days. Visitors can open each event’s time, location and cover inside the widget. Turn on descriptions only if you want them to be public.</p>
                    </section>
                    <iframe src="/embed/upcoming/example" title="Example embedded event calendar" loading="lazy" class="block h-[420px] w-full rounded-xl border border-amber-900/20"></iframe>
                </div>

                <section class="mt-9 max-w-2xl space-y-3 border-t border-amber-900/20 pt-6">
                    <h2 class="text-2xl font-semibold">Change an existing embed</h2>
                    <p class="leading-7">Save changes to the widget’s appearance or filters in EventJunction. Your existing embed will pick them up. If you disable the widget, its embed link is revoked and you’ll need fresh embed code when you enable it again.</p>
                    <p class="leading-7">If you need to control the calendar layout or build your own filters on your site, read the <a href="/docs/api#occurrences" class="underline">calendar occurrences API documentation</a>.</p>
                </section>
            </main>
        </div>
    </div>
</template>
