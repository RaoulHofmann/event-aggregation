<script setup>
import { ref } from 'vue';
import { useForm, usePoll } from '@inertiajs/vue3';
import { Plus, PlugZap, RefreshCw, ExternalLink, ShieldCheck, X } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
defineProps({ connections: Array, canManage: Boolean });
usePoll(10000, { only: ['connections'] });
const showForm = ref(false),
    selected = ref(null),
    disconnecting = ref(null),
    form = useForm({ name: '', api_key: '' }),
    action = useForm({});
const labels = {
    pending: 'Not yet synced',
    queued: 'Sync queued',
    syncing: 'Syncing',
    connected: 'Up to date',
    error: 'Needs attention',
    disconnected: 'Disconnected',
};
const open = (record = null) => {
    selected.value = record;
    form.reset();
    form.clearErrors();
    form.name = record?.name || 'My Humanitix account';
    showForm.value = true;
};
const save = () =>
        form[selected.value ? 'put' : 'post'](
            route(selected.value ? 'integrations.update' : 'integrations.store', selected.value?.id),
            {
                onSuccess: () => {
                    form.reset('api_key');
                    showForm.value = false;
                },
            },
        ),
    close = () => {
        form.reset('api_key');
        showForm.value = false;
    },
    disconnect = () =>
        action.delete(route('integrations.destroy', disconnecting.value.id), {
            onSuccess: () => (disconnecting.value = null),
        });
</script>
<template>
    <AppLayout title="Integrations"
        ><div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="workspace-eyebrow mb-2">Bring your events together</p>
                <h1 class="text-3xl font-semibold">Integrations</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">
                    Keep creating in EventNest. Connect Humanitix to bring those events into the same
                    workspace and API.
                </p>
            </div>
            <button v-if="canManage" class="workspace-primary" @click="open()">
                <Plus class="size-4" />Connect Humanitix
            </button>
        </div>
        <section class="workspace-panel p-6 sm:p-8">
            <div class="flex items-start gap-5">
                <span
                    class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-xl font-bold text-orange-700"
                    >h.</span
                >
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-semibold">Humanitix</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">
                        Import your event details and keep them up to date. Humanitix remains the source for
                        ticketing and edits to imported events.
                    </p>
                    <a
                        href="https://platform.humanitix.com/docs/api"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-4 inline-flex items-center gap-2 text-xs font-medium text-emerald-700"
                        >Humanitix API documentation<ExternalLink class="size-3"
                    /></a>
                </div>
                <span
                    class="hidden shrink-0 rounded-full border px-3 py-1 text-xs text-slate-500 sm:inline-flex"
                    >Inbound sync</span
                >
            </div>
            <div
                v-if="!connections.length"
                class="mt-7 rounded-xl border border-dashed border-slate-200 p-8 text-center"
            >
                <PlugZap class="mx-auto mb-3 size-7 text-slate-400" />
                <h3 class="text-sm font-semibold">No accounts connected yet</h3>
                <p class="mt-2 text-sm text-slate-500">
                    {{
                        canManage
                            ? 'Connect your Humanitix API key to queue your first import.'
                            : 'Ask your workspace owner to connect Humanitix.'
                    }}
                </p>
                <button v-if="canManage" class="workspace-secondary mt-5" @click="open()">
                    Connect an account
                </button>
            </div>
            <article v-for="c in connections" :key="c.id" class="mt-6 rounded-xl border border-slate-200 p-5">
                <div class="flex flex-wrap justify-between gap-3">
                    <h3 class="font-semibold">{{ c.name }}</h3>
                    <span
                        class="rounded-full px-3 py-1 text-xs"
                        :class="
                            c.status === 'error'
                                ? 'bg-red-50 text-red-700'
                                : c.status === 'connected'
                                  ? 'bg-emerald-50 text-emerald-800'
                                  : 'bg-slate-100 text-slate-600'
                        "
                        >{{ labels[c.status] }}</span
                    >
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    Last successful sync:
                    {{ c.last_synced_at ? new Date(c.last_synced_at).toLocaleString() : 'Not synced yet' }}
                </p>
                <p
                    v-if="c.last_error"
                    role="alert"
                    class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800"
                >
                    {{ c.last_error }}
                </p>
                <div v-if="canManage" class="mt-5 flex flex-wrap items-center gap-3">
                    <button
                        v-if="c.status !== 'disconnected'"
                        class="workspace-secondary"
                        :disabled="['queued', 'syncing'].includes(c.status) || action.processing"
                        @click="action.post(route('integrations.sync', c.id))"
                    >
                        <RefreshCw class="size-4" />Sync now</button
                    ><button class="text-xs font-medium text-emerald-700" @click="open(c)">
                        {{ c.status === 'disconnected' ? 'Reconnect' : 'Update API key' }}</button
                    ><button
                        v-if="c.status !== 'disconnected'"
                        class="ml-auto text-xs text-red-700"
                        @click="disconnecting = c"
                    >
                        Disconnect
                    </button>
                </div>
            </article>
        </section>
        <p class="mt-5 flex items-center gap-2 text-xs leading-5 text-slate-500">
            <ShieldCheck class="size-4 shrink-0" />API keys are encrypted and never returned to your browser.
            Imported events are kept when disconnected.
        </p>
        <Modal :show="showForm" max-width="lg" @close="close"
            ><form class="bg-white" @submit.prevent="save">
                <div class="flex justify-between border-b p-6">
                    <h2 class="text-lg font-semibold">
                        {{ selected ? 'Update Humanitix connection' : 'Connect Humanitix' }}
                    </h2>
                    <button type="button" aria-label="Close connection form" @click="close">
                        <X class="size-5 text-slate-400" />
                    </button>
                </div>
                <div class="space-y-5 p-6">
                    <div>
                        <label for="connection-name" class="workspace-label">Connection name</label
                        ><input
                            id="connection-name"
                            v-model="form.name"
                            required
                            class="workspace-input"
                            maxlength="255"
                        />
                    </div>
                    <div>
                        <label for="humanitix-key" class="workspace-label">Humanitix API key</label
                        ><input
                            id="humanitix-key"
                            v-model="form.api_key"
                            type="password"
                            autocomplete="off"
                            required
                            class="workspace-input"
                        />
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            Use the API key from your Humanitix account. EventNest only reads your events.
                        </p>
                    </div>
                    <p
                        v-for="(error, key) in form.errors"
                        :key="key"
                        role="alert"
                        class="text-sm text-red-700"
                    >
                        {{ error }}
                    </p>
                </div>
                <div class="flex justify-end gap-3 border-t bg-slate-50 p-5">
                    <button type="button" class="workspace-secondary" @click="close">Cancel</button
                    ><button class="workspace-primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save and sync' }}
                    </button>
                </div>
            </form></Modal
        ><ConfirmationModal :show="!!disconnecting" @close="disconnecting = null"
            ><template #title>Disconnect Humanitix?</template
            ><template #content
                >Syncing will stop and the saved API key will be removed. Previously imported events stay in
                EventNest.</template
            ><template #footer
                ><button class="workspace-secondary" @click="disconnecting = null">Cancel</button
                ><button class="workspace-primary ml-3" :disabled="action.processing" @click="disconnect">
                    Disconnect
                </button></template
            ></ConfirmationModal
        ></AppLayout
    >
</template>
