<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    eventsOnly: Boolean,
    writePermissions: { type: Array, required: true },
    permissionLabels: { type: Object, required: true },
    workspaceName: String,
});
const emit = defineEmits(['created']);

const form = useForm({
    name: '',
    read: true,
    writes: [],
    recoverable: false,
});
const formId = computed(() => props.eventsOnly ? 'events-key' : 'general-key');
const canSaveCopy = computed(() => props.eventsOnly || (form.read && form.writes.length === 0));
watch(canSaveCopy, (allowed) => {
    if (!allowed) form.recoverable = false;
});

async function createKey() {
    if (form.processing) return;
    form.processing = true;
    form.clearErrors();
    const recoverable = canSaveCopy.value && form.recoverable;
    try {
        const { data } = await axios.post(route('api-tokens.store'), {
            name: form.name,
            recoverable,
            permissions: props.eventsOnly ? ['events:read'] : [...(form.read ? ['read'] : []), ...form.writes],
        }, { headers: { Accept: 'application/json' } });
        form.reset();
        emit('created', data.token, recoverable);
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            for (const [field, messages] of Object.entries(errors)) {
                form.setError(field, messages[0]);
            }
        } else {
            form.setError('permissions', 'Could not create the API key.');
        }
    } finally {
        form.processing = false;
    }
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="createKey">
        <div class="max-w-md">
            <InputLabel :for="formId + '-name'" value="Name" />
            <TextInput
                :id="formId + '-name'"
                v-model="form.name"
                class="mt-1 block w-full"
                type="text"
                maxlength="255"
                required
            />
            <InputError :message="form.errors.name" class="mt-2" />
        </div>

        <template v-if="!eventsOnly">
            <label class="flex items-center gap-2 text-sm">
                <Checkbox v-model:checked="form.read" />
                Read all API data
            </label>
            <fieldset v-if="writePermissions.length">
                <legend class="text-sm font-medium">Optional write permissions</legend>
                <p v-if="workspaceName" class="mt-1 text-sm text-slate-600">Write access is limited to {{ workspaceName }}.</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label v-for="permission in writePermissions" :key="permission" class="flex items-center gap-2 text-sm">
                        <Checkbox v-model:checked="form.writes" :value="permission" />
                        {{ permissionLabels[permission] }}
                    </label>
                </div>
            </fieldset>
        </template>

        <label v-if="canSaveCopy" class="flex items-center gap-2 text-sm">
            <Checkbox v-model:checked="form.recoverable" />
            Save an encrypted copy I can reveal later
        </label>
        <InputError :message="form.errors.permissions || form.errors.recoverable" />

        <PrimaryButton :disabled="form.processing || (!eventsOnly && !form.read && !form.writes.length)">
            {{ eventsOnly ? 'Create events-only key' : 'Create general key' }}
        </PrimaryButton>
    </form>
</template>
