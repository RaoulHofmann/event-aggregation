<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { CalendarDays, X, ArrowRight } from 'lucide-vue-next';
const props = defineProps({ event: Object, templates: Array, fields: Array }),
    emit = defineEmits(['submit', 'cancel']);
const timezone = props.event?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
    local = (v) =>
        v ? new Date(v).toLocaleString('sv-SE', { timeZone: timezone }).slice(0, 16).replace(' ', 'T') : '';
const form = useForm({
    title: props.event?.title || props.event?.event_data?.title || props.event?.event_data?.name || '',
    template_id: props.event?.template_id || '',
    event_data: JSON.parse(JSON.stringify(props.event?.event_data || {})),
    starts_at: local(props.event?.starts_at),
    ends_at: local(props.event?.ends_at),
    timezone,
    status: props.event?.status || 'draft',
    description: props.event?.description || '',
    location: props.event?.location || '',
});
const template = computed(() => props.templates.find((t) => t.id === Number(form.template_id))),
    activeFields = computed(() =>
        (template.value?.fields || []).map((id) => props.fields.find((f) => f.id === id)).filter(Boolean),
    );
const inputType = (f) => ({ datetime: 'datetime-local', decimal: 'number', image: 'url' })[f.type] || f.type,
    options = (f) => {
        if (Array.isArray(f.options)) return f.options;
        try {
            return JSON.parse(f.options || '[]');
        } catch {
            return [];
        }
    };
const submit = () =>
    form[props.event ? 'put' : 'post'](
        route(props.event ? 'events.update' : 'events.store', props.event?.id),
        { preserveScroll: true, onSuccess: () => emit('submit') },
    );
</script>
<template>
    <div class="bg-white">
        <div class="flex items-center justify-between border-b border-slate-100 p-6">
            <div class="flex items-center gap-3">
                <CalendarDays class="size-5 text-emerald-700" />
                <div>
                    <h2 class="text-lg font-semibold">{{ event ? 'Edit event' : 'Create an event' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">Give your next event a home in EventNest.</p>
                </div>
            </div>
            <button aria-label="Close event form" @click="emit('cancel')">
                <X class="size-5 text-slate-400" />
            </button>
        </div>
        <div v-if="!templates.length" class="p-8 text-center">
            <p class="text-sm text-slate-500">Create an event template first.</p>
            <Link :href="route('event-templates.index')" class="workspace-primary mt-5"
                >Manage templates<ArrowRight class="size-4"
            /></Link>
        </div>
        <form v-else @submit.prevent="submit">
            <div class="max-h-[65vh] space-y-6 overflow-y-auto p-6">
                <div>
                    <label for="event-title" class="workspace-label">Event title *</label
                    ><input
                        id="event-title"
                        v-model="form.title"
                        class="workspace-input"
                        required
                        maxlength="255"
                    />
                </div>
                <div>
                    <label for="event-template" class="workspace-label">Event template *</label
                    ><select
                        id="event-template"
                        v-model="form.template_id"
                        class="workspace-input"
                        required
                        @change="
                            form.event_data = {};
                            form.clearErrors();
                        "
                    >
                        <option value="" disabled>Choose an event template</option>
                        <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>
                <details class="rounded-xl border border-slate-200 p-4">
                    <summary class="cursor-pointer text-sm font-medium">Schedule and event details</summary>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="event-start" class="workspace-label">Starts</label
                            ><input
                                id="event-start"
                                v-model="form.starts_at"
                                type="datetime-local"
                                class="workspace-input"
                            />
                        </div>
                        <div>
                            <label for="event-end" class="workspace-label">Ends</label
                            ><input
                                id="event-end"
                                v-model="form.ends_at"
                                type="datetime-local"
                                class="workspace-input"
                            />
                        </div>
                        <div>
                            <label for="event-timezone" class="workspace-label">Timezone</label
                            ><input
                                id="event-timezone"
                                v-model="form.timezone"
                                class="workspace-input"
                                placeholder="Australia/Perth"
                            />
                        </div>
                        <div>
                            <label for="event-status" class="workspace-label">Status</label
                            ><select id="event-status" v-model="form.status" class="workspace-input">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="event-location" class="workspace-label">Location</label
                            ><input id="event-location" v-model="form.location" class="workspace-input" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="event-description" class="workspace-label">Description</label
                            ><textarea
                                id="event-description"
                                v-model="form.description"
                                class="workspace-input"
                                rows="3"
                            />
                        </div>
                    </div>
                </details>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div
                        v-for="f in activeFields"
                        :key="f.id"
                        :class="f.type === 'textarea' ? 'sm:col-span-2' : ''"
                    >
                        <label :for="`event-field-${f.id}`" class="workspace-label"
                            >{{ f.label }}{{ f.required ? ' *' : '' }}</label
                        ><textarea
                            v-if="f.type === 'textarea'"
                            :id="`event-field-${f.id}`"
                            v-model="form.event_data[f.field_id]"
                            :required="f.required"
                            class="workspace-input"
                            rows="4"
                        /><select
                            v-else-if="f.type === 'select'"
                            :id="`event-field-${f.id}`"
                            v-model="form.event_data[f.field_id]"
                            class="workspace-input"
                            :required="f.required"
                        >
                            <option v-for="option in options(f)" :key="option" :value="option">
                                {{ option }}
                            </option></select
                        ><input
                            v-else-if="f.type === 'boolean'"
                            :id="`event-field-${f.id}`"
                            v-model="form.event_data[f.field_id]"
                            type="checkbox"
                            class="rounded text-emerald-700"
                        /><input
                            v-else
                            :id="`event-field-${f.id}`"
                            v-model="form.event_data[f.field_id]"
                            :type="inputType(f)"
                            :required="f.required"
                            :step="f.type === 'decimal' ? 'any' : undefined"
                            class="workspace-input"
                        />
                        <p
                            v-if="form.errors[`event_data.${f.field_id}`]"
                            role="alert"
                            class="mt-2 text-xs text-red-700"
                        >
                            {{ form.errors[`event_data.${f.field_id}`] }}
                        </p>
                    </div>
                </div>
                <p v-for="(error, key) in form.errors" :key="key" role="alert" class="text-sm text-red-700">
                    {{ error }}
                </p>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-5">
                <button type="button" class="workspace-secondary" @click="emit('cancel')">Cancel</button
                ><button class="workspace-primary" :disabled="form.processing || !form.template_id">
                    {{ form.processing ? 'Saving…' : event ? 'Save changes' : 'Create event' }}
                </button>
            </div>
        </form>
    </div>
</template>
