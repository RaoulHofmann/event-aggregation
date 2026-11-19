<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ApiKeyCreateForm from './ApiKeyCreateForm.vue';

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    workspaceName: String,
});

const writePermissions = computed(() => props.availablePermissions.filter(permission => permission !== 'read' && permission !== 'events:read'));
const isEventsOnly = (token) => token.abilities.length === 1 && token.abilities[0] === 'events:read';
const sections = computed(() => [
    { id: 'events-keys', title: 'Events-only keys', eventsOnly: true, keys: props.tokens.filter(isEventsOnly) },
    { id: 'general-keys', title: 'General API keys', eventsOnly: false, keys: props.tokens.filter(token => !isEventsOnly(token)) },
]);
const permissionLabels = {
    read: 'Read all API data',
    'events:read': 'Read events and occurrences only',
    'events:create': 'Create events',
    'events:update': 'Update events',
    'templates:create': 'Create templates and fields',
    'templates:update': 'Update templates and fields',
};

const updateApiTokenForm = useForm({
    permissions: [],
});

const deleteApiTokenForm = useForm({});

const displayingToken = ref(false);
const displayedSecret = ref('');
const savedCopy = ref(false);
const revealing = ref(null);
const tokenError = ref('');
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);

const showCreatedKey = (secret, recoverable) => {
    displayedSecret.value = secret;
    savedCopy.value = recoverable;
    displayingToken.value = true;
    router.reload({ only: ['tokens'] });
};

const revealApiToken = async (token) => {
    if (revealing.value) return;
    revealing.value = token.id;
    tokenError.value = '';
    try {
        const { data } = await axios.post(route('api-tokens.reveal', token.id), {}, { headers: { Accept: 'application/json' } });
        displayedSecret.value = data.token;
        savedCopy.value = true;
        displayingToken.value = true;
    } catch {
        tokenError.value = 'Could not show this API key.';
    } finally {
        revealing.value = null;
    }
};

const closeToken = () => {
    displayingToken.value = false;
    displayedSecret.value = '';
};

const manageApiTokenPermissions = (token) => {
    updateApiTokenForm.permissions = token.abilities;
    updateApiTokenForm.clearErrors();
    managingPermissionsFor.value = token;
};

const updateApiToken = () => {
    updateApiTokenForm.put(route('api-tokens.update', managingPermissionsFor.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (managingPermissionsFor.value = null),
    });
};

const confirmApiTokenDeletion = (token) => {
    apiTokenBeingDeleted.value = token;
};

const deleteApiToken = () => {
    deleteApiTokenForm.delete(route('api-tokens.destroy', apiTokenBeingDeleted.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (apiTokenBeingDeleted.value = null),
    });
};
</script>

<template>
    <div class="space-y-6">
        <p v-if="tokenError" role="alert" class="text-sm text-red-700">{{ tokenError }}</p>
        <section v-for="section in sections" :key="section.id" :aria-labelledby="section.id" class="workspace-panel p-5 sm:p-7">
            <header class="mb-5">
                <h2 :id="section.id" class="text-xl font-semibold">{{ section.title }}</h2>
                <p class="mt-1 text-sm text-slate-600">
                    <template v-if="section.eventsOnly">
                        Read-only access to events and calendar occurrences in {{ workspaceName || 'the selected workspace' }}.
                    </template>
                    <template v-else>Choose read access and any write permissions your app needs.</template>
                </p>
            </header>

            <ApiKeyCreateForm
                :events-only="section.eventsOnly"
                :workspace-name="workspaceName"
                :write-permissions="writePermissions"
                :permission-labels="permissionLabels"
                @created="showCreatedKey"
            />

            <ul v-if="section.keys.length" class="mt-6 divide-y divide-slate-200 border-t border-slate-200" aria-label="Existing keys">
                <li v-for="token in section.keys" :key="token.id" class="flex flex-wrap items-center justify-between gap-2 py-4 last:pb-0">
                    <div class="min-w-0 break-words">
                        <span class="font-medium">{{ token.name }}</span>
                        <span v-if="token.workspace_name" class="block text-xs text-slate-500">{{ token.workspace_name }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                        <span v-if="token.last_used_ago" class="text-slate-500">Last used {{ token.last_used_ago }}</span>
                        <button v-if="token.can_reveal" class="text-emerald-800 underline" :disabled="revealing !== null" @click="revealApiToken(token)">
                            Reveal key
                        </button>
                        <button v-if="!section.eventsOnly && token.abilities.length" class="text-slate-700 underline" @click="manageApiTokenPermissions(token)">
                            Permissions
                        </button>
                        <button class="text-red-700" @click="confirmApiTokenDeletion(token)">Delete</button>
                    </div>
                </li>
            </ul>
        </section>

        <!-- Token Value Modal -->
        <DialogModal :show="displayingToken" @close="closeToken">
            <template #title>
                API Token
            </template>

            <template #content>
                <div>
                    {{ savedCopy ? 'You can reveal this read-only key again from API Access.' : 'Copy this key now. It is shown only once.' }}
                </div>

                <div v-if="displayedSecret" class="mt-4 bg-gray-100 dark:bg-gray-900 px-4 py-2 rounded font-mono text-sm text-gray-500 break-all">
                    {{ displayedSecret }}
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeToken">
                    Close
                </SecondaryButton>
            </template>
        </DialogModal>

        <!-- API Token Permissions Modal -->
        <DialogModal :show="managingPermissionsFor != null" @close="managingPermissionsFor = null">
            <template #title>
                API Token Permissions
            </template>

            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="permission in managingPermissionsFor?.abilities" :key="permission">
                        <label class="flex items-center">
                            <Checkbox v-model:checked="updateApiTokenForm.permissions" :value="permission" />
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ permissionLabels[permission] ?? permission }}</span>
                        </label>
                    </div>
                </div>
                <InputError :message="updateApiTokenForm.errors.permissions" class="mt-2" />
            </template>

            <template #footer>
                <SecondaryButton @click="managingPermissionsFor = null">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    class="ms-3"
                    :class="{ 'opacity-25': updateApiTokenForm.processing }"
                    :disabled="updateApiTokenForm.processing"
                    @click="updateApiToken"
                >
                    Save
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Delete Token Confirmation Modal -->
        <ConfirmationModal :show="apiTokenBeingDeleted != null" @close="apiTokenBeingDeleted = null">
            <template #title>
                Delete API Token
            </template>

            <template #content>
                Are you sure you would like to delete this API token?
            </template>

            <template #footer>
                <SecondaryButton @click="apiTokenBeingDeleted = null">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': deleteApiTokenForm.processing }"
                    :disabled="deleteApiTokenForm.processing"
                    @click="deleteApiToken"
                >
                    Delete
                </DangerButton>
            </template>
        </ConfirmationModal>
    </div>
</template>
