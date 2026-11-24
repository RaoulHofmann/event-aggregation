<script setup>
import { router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({ connectionId: Number });
const form = useForm({ connection: props.connectionId, consent: false });
const close = () => router.visit(route("integrations.index"));
</script>

<template>
    <AppLayout title="Connect Google Calendar">
        <Modal show max-width="lg" @close="close">
            <form class="space-y-4 bg-white p-6 sm:p-8" @submit.prevent="form.post(route('integrations.google.authorize'))">
                <h1 class="text-2xl font-semibold">Connect Google Calendar</h1>
                <p class="text-sm leading-6 text-slate-700">EventJunction reads your calendar list so you can choose which calendars to import. It reads titles, descriptions, locations, dates and related details from events on the selected calendars and stores them as drafts in this workspace. Encrypted Google credentials let scheduled imports keep them up to date.</p>
                <p class="text-sm leading-6 text-slate-700">Workspace members and authorised API clients can access imported data. Published events can appear in your public widget if you enable it. You can disconnect Google or request deletion of imported data. <a href="/privacy-policy" target="_blank" rel="noopener noreferrer" class="font-medium text-emerald-800 underline">Privacy policy</a></p>
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                    <input v-model="form.consent" type="checkbox" required class="mt-0.5" />
                    <span>I agree to connect Google Calendar and use the selected calendar data in this workspace as described above.</span>
                </label>
                <p v-if="form.errors.consent" class="text-sm text-red-700" role="alert">{{ form.errors.consent }}</p>
                <div class="flex flex-wrap justify-end gap-3">
                    <button type="button" class="workspace-secondary" @click="close">Cancel</button>
                    <button type="submit" class="workspace-primary" :disabled="!form.consent || form.processing">Continue to Google</button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
