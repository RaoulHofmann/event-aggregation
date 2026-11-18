<script setup>
import { ref, watch } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    stats: Object,
    filters: Object,
    users: Object,
    teams: Object,
    activity: Array,
});

const search = ref("");
const status = ref("");
const teamSearch = ref("");
const renaming = ref(null);
const form = useForm({ action: "", name: "" });

watch(() => props.filters, (filters) => {
    search.value = filters.search ?? "";
    status.value = filters.status ?? "";
    teamSearch.value = filters.team_search ?? "";
}, { immediate: true });

const cards = [
    ["pending", "Pending registrations"],
    ["active", "Active accounts"],
    ["new_registrations", "New accounts · 7 days"],
    ["teams", "Workspaces"],
    ["paused_teams", "Paused workspaces"],
    ["queued_jobs", "Unreserved jobs"],
    ["failed_jobs", "Failed jobs · 24 hours"],
];
const statusLabels = {
    active: "Active",
    pending: "Pending",
    suspended: "Suspended",
    rejected: "Declined",
};
const activityLabels = {
    approve: "Approved registration",
    reject: "Declined registration",
    suspend: "Suspended user",
    restore: "Restored user",
    pause: "Paused workspace",
    resume: "Resumed workspace",
    rename: "Renamed workspace",
};
const date = (value) => value ? new Date(value).toLocaleString() : "No completed scan recorded";
const filter = () => router.get(route("admin.index"), {
    search: search.value || undefined,
    status: status.value || undefined,
    team_search: teamSearch.value || undefined,
}, { preserveScroll: true });
const change = (target, row, action) => {
    if (["reject", "suspend", "pause"].includes(action) && !window.confirm(
        action === "suspend" ? `Suspend ${row.name} and revoke their API tokens?`
            : action === "pause" ? `Pause access and imports for ${row.name}?`
            : `Decline ${row.name}'s registration?`
    )) return;
    form.clearErrors();
    form.action = action;
    form.name = "";
    form.patch(route(`admin.${target}.update`, row.id), { preserveScroll: true });
};
const startRename = (team) => {
    renaming.value = team.id;
    form.name = team.name;
    form.clearErrors();
};
const rename = (team) => {
    form.action = "rename";
    form.patch(route("admin.teams.update", team.id), {
        preserveScroll: true,
        onSuccess: () => renaming.value = null,
    });
};
</script>

<template>
    <AppLayout title="Administration" wide>
        <header class="mb-7">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Administration</h1>
            <p class="mt-2 text-sm text-slate-600">Review registrations, manage access, and monitor workspace activity.</p>
        </header>

        <section aria-label="Platform statistics" class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-7">
            <div v-for="[key, label] in cards" :key="key" class="workspace-panel px-4 py-4">
                <p class="text-xs font-medium leading-5 text-slate-600">{{ label }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-950">{{ stats[key] }}</p>
            </div>
        </section>

        <div class="mb-8 grid gap-6 2xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
            <section class="workspace-panel min-w-0 p-5 sm:p-6" aria-labelledby="admin-users">
                <h2 id="admin-users" class="text-xl font-semibold text-slate-950">Users</h2>
                <form class="my-4 flex flex-wrap items-end gap-3" @submit.prevent="filter">
                    <label class="min-w-40 flex-1 text-xs font-medium text-slate-700">
                        Name or email
                        <input v-model="search" maxlength="100" type="search" class="workspace-input mt-1 block w-full" />
                    </label>
                    <label class="text-xs font-medium text-slate-700">
                        Status
                        <select v-model="status" class="workspace-input mt-1 block">
                            <option value="">All statuses</option>
                            <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </label>
                    <button class="workspace-secondary">Filter users</button>
                </form>
                <p v-if="!users.data.length" class="py-6 text-sm text-slate-500">No users match this filter.</p>
                <ul v-else class="divide-y divide-slate-200">
                    <li v-for="user in users.data" :key="user.id" class="flex flex-wrap items-center justify-between gap-3 py-4">
                        <div class="min-w-0">
                            <p class="break-words font-medium text-slate-950">{{ user.name }}</p>
                            <p class="break-all text-sm text-slate-600">{{ user.email }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ date(user.created_at) }} · {{ user.owned_teams_count }} owned
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="user.account_status === 'pending' ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-700'">
                                {{ user.is_platform_admin ? "Administrator" : statusLabels[user.account_status] }}
                            </span>
                            <template v-if="!user.is_platform_admin">
                                <button v-if="['pending', 'rejected'].includes(user.account_status)" class="workspace-primary" :disabled="form.processing" @click="change('users', user, 'approve')">Approve</button>
                                <button v-if="user.account_status === 'pending'" class="workspace-secondary" :disabled="form.processing" @click="change('users', user, 'reject')">Decline</button>
                                <button v-if="user.account_status === 'active'" class="workspace-secondary" :disabled="form.processing" @click="change('users', user, 'suspend')">Suspend</button>
                                <button v-if="user.account_status === 'suspended'" class="workspace-secondary" :disabled="form.processing" @click="change('users', user, 'restore')">Restore</button>
                            </template>
                        </div>
                    </li>
                </ul>
                <nav v-if="users.last_page > 1" aria-label="User pages" class="mt-5 flex items-center justify-between gap-3 text-sm">
                    <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="workspace-secondary" preserve-scroll>Previous</Link>
                    <span>Page {{ users.current_page }} of {{ users.last_page }}</span>
                    <Link v-if="users.next_page_url" :href="users.next_page_url" class="workspace-secondary" preserve-scroll>Next</Link>
                </nav>
            </section>

            <section class="workspace-panel min-w-0 p-5 sm:p-6" aria-labelledby="admin-teams">
                <h2 id="admin-teams" class="text-xl font-semibold text-slate-950">Workspaces</h2>
                <form class="my-4 flex flex-wrap items-end gap-3" @submit.prevent="filter">
                    <label class="min-w-40 flex-1 text-xs font-medium text-slate-700">
                        Workspace name
                        <input v-model="teamSearch" maxlength="100" type="search" class="workspace-input mt-1 block w-full" />
                    </label>
                    <button class="workspace-secondary">Filter workspaces</button>
                </form>
                <p v-if="!teams.data.length" class="py-6 text-sm text-slate-500">No workspaces match this filter.</p>
                <ul v-else class="divide-y divide-slate-200">
                    <li v-for="team in teams.data" :key="team.id" class="py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-words font-medium text-slate-950">{{ team.name }}</p>
                                <p class="break-words text-sm text-slate-600">{{ team.owner?.name || "Unknown owner" }} · {{ team.users_count + 1 }} members</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700">{{ team.suspended_at ? "Paused" : "Active" }}</span>
                        </div>
                        <form v-if="renaming === team.id" class="mt-3 flex flex-wrap gap-2" @submit.prevent="rename(team)">
                            <input v-model="form.name" maxlength="255" required aria-label="New workspace name" class="workspace-input min-w-0 flex-1" />
                            <button class="workspace-primary" :disabled="form.processing">Save name</button>
                            <button type="button" class="workspace-secondary" @click="renaming = null">Cancel</button>
                            <p v-if="form.errors.name" class="w-full text-sm text-red-700">{{ form.errors.name }}</p>
                        </form>
                        <div v-else class="mt-3 flex flex-wrap gap-2">
                            <button class="workspace-secondary" :disabled="form.processing" @click="startRename(team)">Rename</button>
                            <button v-if="team.suspended_at || !team.owner?.is_platform_admin" class="workspace-secondary" :disabled="form.processing" @click="change('teams', team, team.suspended_at ? 'resume' : 'pause')">{{ team.suspended_at ? "Resume" : "Pause" }}</button>
                        </div>
                    </li>
                </ul>
                <nav v-if="teams.last_page > 1" aria-label="Workspace pages" class="mt-5 flex items-center justify-between gap-3 text-sm">
                    <Link v-if="teams.prev_page_url" :href="teams.prev_page_url" class="workspace-secondary" preserve-scroll>Previous</Link>
                    <span>Page {{ teams.current_page }} of {{ teams.last_page }}</span>
                    <Link v-if="teams.next_page_url" :href="teams.next_page_url" class="workspace-secondary" preserve-scroll>Next</Link>
                </nav>
            </section>
        </div>

        <section class="workspace-panel p-5 sm:p-6" aria-labelledby="admin-activity">
            <h2 id="admin-activity" class="text-xl font-semibold text-slate-950">Recent admin actions</h2>
            <p class="mb-4 mt-2 text-sm text-slate-600">Last integration scheduler scan · {{ date(stats.last_sync_scan) }}</p>
            <p v-if="!activity.length" class="py-4 text-sm text-slate-500">No admin actions recorded yet.</p>
            <ul v-else class="divide-y divide-slate-200">
                <li v-for="entry in activity" :key="entry.id" class="flex flex-wrap items-baseline justify-between gap-2 py-3 text-sm">
                    <span>{{ activityLabels[entry.action] }} · {{ entry.target_type === "user" ? "User" : "Workspace" }} #{{ entry.target_id }}</span>
                    <span class="text-slate-600">{{ entry.actor ?? "Administrator" }} · {{ date(entry.created_at) }}</span>
                </li>
            </ul>
        </section>
    </AppLayout>
</template>
