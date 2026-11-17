<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    LayoutDashboard,
    Layers,
    SlidersHorizontal,
    KeyRound,
    PlugZap,
    Users,
    Menu,
    X,
    ChevronDown,
    LogOut,
    Check,
    Plus,
} from 'lucide-vue-next';
import EventNestMark from '@/Components/EventNestMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
defineProps({ title: { type: String, default: 'Workspace' } });
const page = usePage(),
    user = computed(() => page.props.auth.user),
    mobileOpen = ref(false);
watch(
    () => page.url,
    () => (mobileOpen.value = false),
);
const items = [
    ['Overview', LayoutDashboard, 'dashboard', 'dashboard'],
    ['Events', CalendarDays, 'events.index', 'events.*'],
    ['Event templates', Layers, 'event-templates.index', 'event-templates.*'],
    ['Custom fields', SlidersHorizontal, 'field-templates.index', 'field-templates.*'],
    ['Integrations', PlugZap, 'integrations.index', 'integrations.*'],
    ['API access', KeyRound, 'api-tokens.index', 'api-tokens.*'],
];
const initials = computed(() =>
    user.value.name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);
const switchTeam = (t) =>
    router.put(route('current-team.update'), { team_id: t.id }, { preserveState: false });
</script>
<template>
    <div class="min-h-screen bg-slate-50" @keydown.esc="mobileOpen = false">
        <Head :title="title" /><a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:z-[100] focus:bg-white focus:p-3"
            >Skip to content</a
        ><button
            v-if="mobileOpen"
            aria-label="Close navigation"
            class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
            @click="mobileOpen = false"
        />
        <aside
            id="workspace-navigation"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-950 text-slate-300 transition-transform motion-reduce:transition-none"
        >
            <div class="flex h-24 items-center justify-between px-7">
                <Link :href="route('dashboard')" class="flex items-center gap-3 text-xl font-bold text-white"
                    ><EventNestMark class="size-9" />EventNest</Link
                ><button class="p-2 lg:hidden" aria-label="Close navigation" @click="mobileOpen = false">
                    <X class="size-5" />
                </button>
            </div>
            <p class="px-7 pb-3 pt-5 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">
                Workspace
            </p>
            <nav aria-label="Main navigation" class="space-y-1 px-4">
                <Link
                    v-for="item in items"
                    :key="item[2]"
                    :href="route(item[2])"
                    :aria-current="route().current(item[3]) ? 'page' : undefined"
                    :class="
                        route().current(item[3])
                            ? 'bg-emerald-400/10 text-emerald-300'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white'
                    "
                    class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium"
                    ><component :is="item[1]" class="size-[18px]" />{{ item[0] }}</Link
                >
            </nav>
            <div class="mx-7 my-7 border-t border-slate-800" />
            <Link
                v-if="user.current_team"
                :href="route('teams.show', user.current_team)"
                class="mx-4 flex items-center gap-3 rounded-lg px-3 py-3 text-sm text-slate-400 hover:bg-white/5"
                ><Users class="size-[18px]" />Team settings</Link
            >
            <div class="mt-auto border-t border-slate-800 p-4">
                <Dropdown align="left" width="60" upward
                    ><template #trigger
                        ><button
                            class="flex w-full items-center gap-3 rounded-xl p-2 text-left hover:bg-white/5"
                            aria-label="Account menu"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold"
                                >{{ initials }}</span
                            ><span class="min-w-0 flex-1"
                                ><span class="block truncate text-sm text-white">{{ user.name }}</span
                                ><span class="block truncate text-xs text-slate-500">{{
                                    user.email
                                }}</span></span
                            ><ChevronDown class="size-4" /></button></template
                    ><template #content
                        ><DropdownLink :href="route('profile.show')">Your profile</DropdownLink>
                        <form @submit.prevent="router.post(route('logout'))">
                            <DropdownLink as="button">Log out</DropdownLink>
                        </form></template
                    ></Dropdown
                >
            </div>
        </aside>
        <div class="lg:pl-64">
            <header
                class="flex h-20 items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 sm:px-8 xl:px-12"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        class="p-2 lg:hidden"
                        aria-label="Open navigation"
                        aria-controls="workspace-navigation"
                        :aria-expanded="mobileOpen"
                        @click="mobileOpen = true"
                    >
                        <Menu class="size-5" /></button
                    ><span class="hidden text-sm text-slate-400 sm:inline">Workspace /</span
                    ><span class="truncate text-sm font-medium">{{ title }}</span>
                </div>
                <Dropdown v-if="$page.props.jetstream.hasTeamFeatures" width="60"
                    ><template #trigger
                        ><button
                            class="flex max-w-[220px] items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"
                        >
                            <span class="size-2 shrink-0 rounded-full bg-emerald-500" /><span
                                class="truncate"
                                >{{ user.current_team?.name || 'Choose workspace' }}</span
                            ><ChevronDown class="size-4 shrink-0" /></button></template
                    ><template #content
                        ><p class="px-4 py-2 text-xs text-slate-500">Switch workspace</p>
                        <button
                            v-for="team in user.all_teams"
                            :key="team.id"
                            class="flex w-full justify-between px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                            @click="switchTeam(team)"
                        >
                            {{ team.name
                            }}<Check
                                v-if="team.id === user.current_team_id"
                                class="size-4 text-emerald-700"
                            /></button
                        ><DropdownLink
                            v-if="$page.props.jetstream.canCreateTeams"
                            :href="route('teams.create')"
                            >Create workspace</DropdownLink
                        ></template
                    ></Dropdown
                >
            </header>
            <main
                id="main-content"
                tabindex="-1"
                class="mx-auto max-w-[1600px] px-5 py-8 sm:px-8 xl:px-12 xl:py-10"
            >
                <p
                    v-if="$page.props.flash?.success"
                    role="status"
                    class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                >
                    {{ $page.props.flash.success }}
                </p>
                <p
                    v-if="$page.props.flash?.error"
                    role="alert"
                    class="mb-6 rounded-xl bg-red-50 px-4 py-3 text-red-800"
                >
                    {{ $page.props.flash.error }}
                </p>
                <div v-if="$slots.header" class="mb-7"><slot name="header" /></div>
                <slot />
            </main>
        </div>
    </div>
</template>
