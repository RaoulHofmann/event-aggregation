<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import ActionSection from '@/Components/ActionSection.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
    workspaceName: String,
});

const createApiTokenForm = useForm({
    name: '',
    permissions: [],
    recoverable: false,
});
const readScope = ref(props.defaultPermissions[0] ?? 'read');
const writePermissions = computed(() => props.availablePermissions.filter(permission => permission !== 'read' && permission !== 'events:read'));
const canSaveCopy = computed(() => !!readScope.value && createApiTokenForm.permissions.length === 0);
watch(canSaveCopy, (allowed) => {
    if (!allowed) createApiTokenForm.recoverable = false;
});
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
const creating = ref(false);
const revealing = ref(null);
const tokenError = ref('');
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);

const createApiToken = async () => {
    if (creating.value) return;
    creating.value = true;
    createApiTokenForm.clearErrors();
    const recoverable = canSaveCopy.value && createApiTokenForm.recoverable;
    try {
        const { data } = await axios.post(route('api-tokens.store'), {
            name: createApiTokenForm.name,
            recoverable,
            permissions: [...(readScope.value ? [readScope.value] : []), ...createApiTokenForm.permissions],
        }, { headers: { Accept: 'application/json' } });
        displayedSecret.value = data.token;
        savedCopy.value = recoverable;
        displayingToken.value = true;
        createApiTokenForm.reset();
        readScope.value = props.defaultPermissions[0] ?? 'read';
        router.reload({ only: ['tokens'] });
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            for (const [field, messages] of Object.entries(errors)) {
                createApiTokenForm.setError(field, messages[0]);
            }
        } else {
            createApiTokenForm.setError('permissions', 'Could not create the API key.');
        }
    } finally {
        creating.value = false;
    }
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
    <div>
        <!-- Generate API Token -->
        <FormSection @submitted="createApiToken">
            <template #title>
                Create API Token
            </template>

            <template #description>
                Read access is selected by default. Write permissions apply only to {{ workspaceName || 'the selected workspace' }}.
            </template>

            <template #form>
                <!-- Token Name -->
                <div class="col-span-6 sm:col-span-4">
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        v-model="createApiTokenForm.name"
                        type="text"
                        class="mt-1 block w-full"
                        maxlength="255"
                        required
                        autofocus
                    />
                    <InputError :message="createApiTokenForm.errors.name" class="mt-2" />
                </div>

                <!-- Token Permissions -->
                <div class="col-span-6">
                    <InputLabel for="read-scope" value="Read access" />
                    <select id="read-scope" v-model="readScope" class="workspace-input mt-1">
                        <option value="read">All API data</option>
                        <option value="events:read">Events and occurrences only</option>
                        <option v-if="writePermissions.length" value="">No read access</option>
                    </select>
                    <p v-if="readScope === 'events:read'" class="mt-2 text-sm text-gray-600">
                        The key can read events and occurrences from {{ workspaceName || 'the selected workspace' }}.
                    </p>
                </div>
                <fieldset v-if="writePermissions.length" class="col-span-6">
                    <legend class="text-sm font-medium">Write permissions</legend>

                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="permission in writePermissions" :key="permission">
                            <label class="flex items-center">
                                <Checkbox v-model:checked="createApiTokenForm.permissions" :value="permission" />
                                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ permissionLabels[permission] }}</span>
                            </label>
                        </div>
                    </div>
                </fieldset>
                <div class="col-span-6">
                    <label v-if="canSaveCopy" class="flex items-center gap-2 text-sm">
                        <Checkbox v-model:checked="createApiTokenForm.recoverable" />
                        Save an encrypted copy I can reveal later
                    </label>
                    <InputError :message="createApiTokenForm.errors.permissions || createApiTokenForm.errors.recoverable" class="mt-2" />
                </div>
            </template>

            <template #actions>
                <PrimaryButton :disabled="creating || (!readScope && !createApiTokenForm.permissions.length)">
                    Create
                </PrimaryButton>
            </template>
        </FormSection>

        <div v-if="tokens.length > 0">
            <SectionBorder />

            <!-- Manage API Tokens -->
            <div class="mt-10 sm:mt-0">
                <ActionSection>
                    <template #title>
                        Manage API Tokens
                    </template>

                    <template #description>
                        You may delete any of your existing tokens if they are no longer needed.
                    </template>

                    <!-- API Token List -->
                    <template #content>
                        <div class="space-y-6">
                            <p v-if="tokenError" role="alert" class="text-sm text-red-700">{{ tokenError }}</p>
                            <div v-for="token in tokens" :key="token.id" class="flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0 break-words dark:text-white">
                                    {{ token.name }}
                                    <span v-if="token.workspace_name" class="block text-xs text-gray-500">{{ token.workspace_name }}</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                    <button v-if="token.can_reveal" class="cursor-pointer text-sm text-emerald-800 underline" :disabled="revealing !== null" @click="revealApiToken(token)">
                                        Reveal key
                                    </button>
                                    <div v-if="token.last_used_ago" class="text-sm text-gray-400">
                                        Last used {{ token.last_used_ago }}
                                    </div>

                                    <button
                                        v-if="availablePermissions.length > 0"
                                        class="cursor-pointer text-sm text-gray-600 underline"
                                        @click="manageApiTokenPermissions(token)"
                                    >
                                        Permissions
                                    </button>

                                    <button class="cursor-pointer text-sm text-red-700" @click="confirmApiTokenDeletion(token)">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </ActionSection>
            </div>
        </div>

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
