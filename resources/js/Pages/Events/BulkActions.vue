<script setup>
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({ ids: { type: Array, required: true } });
const emit = defineEmits(["complete", "clear"]);
const page = usePage();
const mode = ref(null);
const form = useForm({ workspace_id: null, ids: [], status: "", location: "", description: "" });
const changeLocation = ref(false);
const changeDescription = ref(false);
const hasChanges = computed(() => !!form.status || changeLocation.value || changeDescription.value);
const eventCount = computed(() => `${form.ids.length} ${form.ids.length === 1 ? "event" : "events"}`);

function open(action) {
    form.reset();
    form.clearErrors();
    changeLocation.value = false;
    changeDescription.value = false;
    form.ids = [...props.ids];
    form.workspace_id = page.props.auth.user.current_team_id;
    mode.value = action;
}
function close() {
    mode.value = null;
}
function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            close();
            emit("complete");
        },
    };
    form.transform(({ workspace_id, ids, status, location, description }) => ({
        workspace_id,
        ids,
        ...(mode.value === "edit" && status ? { status } : {}),
        ...(mode.value === "edit" && changeLocation.value ? { location } : {}),
        ...(mode.value === "edit" && changeDescription.value ? { description } : {}),
    }));
    if (mode.value === "edit") {
        form.patch(route("events.bulk.update"), options);
    } else {
        form.delete(route("events.bulk.destroy"), options);
    }
}
</script>

<template>
    <div v-if="ids.length" class="flex flex-wrap items-center gap-3 border-b bg-slate-50 p-4">
        <strong class="text-sm" aria-live="polite">{{ ids.length }} selected</strong>
        <button type="button" class="workspace-secondary" @click="open('edit')">Edit selected</button>
        <button type="button" class="workspace-secondary text-red-700" @click="open('delete')">Delete selected</button>
        <button type="button" class="text-sm underline" @click="emit('clear')">Clear selection</button>
    </div>

    <Modal :show="mode === 'edit'" max-width="lg" :closeable="!form.processing" @close="close">
        <form class="space-y-4 bg-white p-6" @submit.prevent="submit">
            <h2 class="text-xl font-semibold">Edit {{ eventCount }}</h2>
            <label for="bulk-status" class="workspace-label">Status</label>
            <select id="bulk-status" v-model="form.status" class="workspace-input">
                <option value="">Keep current status</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <div>
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input v-model="changeLocation" type="checkbox" /> Change location
                </label>
                <input v-if="changeLocation" v-model="form.location" aria-label="New location" placeholder="Leave blank to clear" maxlength="5000" class="workspace-input mt-2" />
            </div>
            <div>
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input v-model="changeDescription" type="checkbox" /> Change description
                </label>
                <textarea v-if="changeDescription" v-model="form.description" aria-label="New description" placeholder="Leave blank to clear" maxlength="100000" rows="4" class="workspace-input mt-2" />
            </div>
            <p v-for="(error, field) in form.errors" :key="field" role="alert" class="text-sm text-red-700">{{ error }}</p>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" class="workspace-secondary" :disabled="form.processing" @click="close">Cancel</button>
                <button class="workspace-primary" :disabled="!hasChanges || form.processing">Apply changes</button>
            </div>
        </form>
    </Modal>

    <ConfirmationModal :show="mode === 'delete'" max-width="lg" :closeable="!form.processing" @close="close">
        <template #title>Delete {{ eventCount }}?</template>
        <template #content>
            <p>Repeating events will be deleted as entire series. Imported events will stay hidden after future syncs.</p>
            <p v-for="(error, field) in form.errors" :key="field" role="alert" class="mt-2 text-red-700">{{ error }}</p>
        </template>
        <template #footer>
            <button type="button" class="workspace-secondary mr-2" :disabled="form.processing" @click="close">Cancel</button>
            <button type="button" class="workspace-primary !bg-red-700 hover:!bg-red-800" :disabled="form.processing" @click="submit">Delete {{ eventCount }}</button>
        </template>
    </ConfirmationModal>
</template>
