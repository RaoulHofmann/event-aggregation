<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, PlugZap, Braces, ArrowRight, Check } from 'lucide-vue-next';
import EventNestMark from '@/Components/EventNestMark.vue';
defineProps({ canLogin: Boolean, canRegister: Boolean });
const features = [
    [
        CalendarDays,
        'Make it your own',
        'Create events with reusable templates and the custom fields your team needs.',
    ],
    [PlugZap, 'Bring Humanitix along', 'Import events from Humanitix into the same workspace.'],
    [Braces, 'Build with one API', 'Use one consistent event API for your website or application.'],
];
</script>
<template>
    <Head title="A home for all your events" />
    <div class="min-h-screen bg-slate-50">
        <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-7 sm:px-10">
            <Link href="/" class="flex items-center gap-3 text-xl font-bold"
                ><EventNestMark class="size-10" />EventNest</Link
            >
            <nav aria-label="Account" class="flex items-center gap-5">
                <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="workspace-primary"
                    >Open workspace</Link
                ><template v-else
                    ><Link v-if="canLogin" :href="route('login')" class="text-sm font-semibold text-slate-600"
                        >Log in</Link
                    ><Link v-if="canRegister" :href="route('register')" class="workspace-primary"
                        >Get started<ArrowRight class="size-4" /></Link
                ></template>
            </nav>
        </header>
        <main class="mx-auto max-w-7xl px-6 pb-16 pt-12 sm:px-10 lg:pt-24">
            <section class="grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
                <div>
                    <p class="workspace-eyebrow mb-6 text-emerald-700">Create. Connect. Share.</p>
                    <h1
                        class="text-5xl font-semibold leading-[1.08] tracking-tight text-slate-950 sm:text-6xl"
                    >
                        Every event.<br /><span class="text-emerald-700">One place.</span>
                    </h1>
                    <p class="mt-7 max-w-lg text-base leading-8 text-slate-500">
                        Give your events a home. Create them in EventNest, bring them in from Humanitix, and
                        share them through one simple API.
                    </p>
                    <Link
                        :href="
                            route($page.props.auth.user ? 'dashboard' : canRegister ? 'register' : 'login')
                        "
                        class="workspace-primary mt-8 px-6 py-3.5"
                        >{{ $page.props.auth.user ? 'Go to your workspace' : 'Create your workspace'
                        }}<ArrowRight class="size-4"
                    /></Link>
                    <p class="mt-5 text-xs text-slate-500">Your team. Your templates. Your event data.</p>
                </div>
                <div
                    aria-label="Create and connect through one EventNest API"
                    class="rounded-3xl bg-slate-950 p-7 text-white shadow-2xl shadow-slate-900/10 sm:p-10"
                >
                    <div class="mb-8 flex items-center gap-3">
                        <EventNestMark class="size-9" /><span class="font-semibold"
                            >Your event workspace</span
                        >
                    </div>
                    <div
                        v-for="item in [
                            [CalendarDays, 'Created in EventNest', 'Your own events, your way'],
                            [PlugZap, 'Connected from Humanitix', 'Bring your existing events together'],
                        ]"
                        :key="item[1]"
                        class="mb-4 flex items-center gap-4 rounded-xl border border-slate-700 bg-slate-800/60 p-5"
                    >
                        <component :is="item[0]" class="size-6 text-emerald-300" />
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ item[1] }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ item[2] }}</p>
                        </div>
                        <Check class="size-4 text-emerald-300" />
                    </div>
                    <div class="mx-auto h-9 w-px bg-slate-700" />
                    <div class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-5">
                        <p class="flex items-center gap-3 text-sm font-semibold text-emerald-300">
                            <Braces class="size-5" />One EventNest API
                        </p>
                        <p class="mt-3 font-mono text-xs text-slate-400">GET /api/v1/events</p>
                    </div>
                </div>
            </section>
            <section
                aria-label="EventNest features"
                class="mt-20 grid gap-6 border-t border-slate-200 pt-12 md:grid-cols-3"
            >
                <article v-for="f in features" :key="f[1]" class="py-3">
                    <component :is="f[0]" class="mb-5 size-6 text-emerald-700" />
                    <h2 class="text-lg font-semibold">{{ f[1] }}</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-500">{{ f[2] }}</p>
                </article>
            </section>
        </main>
        <footer class="mx-auto max-w-7xl border-t px-6 py-7 text-xs text-slate-500">
            EventNest · A home for all your events.
        </footer>
    </div>
</template>
