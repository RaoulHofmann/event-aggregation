<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";
import { Link, useForm } from "@inertiajs/vue3";
import { CalendarDays, X, ArrowRight } from "lucide-vue-next";
const props = defineProps({
        event: Object,
        templates: Array,
        fields: Array,
        initialDate: String,
    }),
    emit = defineEmits(["submit", "cancel"]);
const timezone =
        props.event?.timezone ||
        Intl.DateTimeFormat().resolvedOptions().timeZone ||
        "UTC",
    local = (v) =>
        v
            ? new Date(v)
                  .toLocaleString("sv-SE", { timeZone: timezone })
                  .slice(0, 16)
                  .replace(" ", "T")
            : "";
const form = useForm({
    title:
        props.event?.title ||
        props.event?.event_data?.title ||
        props.event?.event_data?.name ||
        "",
    template_id: props.event?.template_id || "",
    event_data: JSON.parse(JSON.stringify(props.event?.event_data || {})),
    starts_at:
        local(props.event?.starts_at) ||
        (props.initialDate ? `${props.initialDate}T09:00` : ""),
    ends_at:
        local(props.event?.ends_at) ||
        (props.initialDate ? `${props.initialDate}T10:00` : ""),
    timezone,
    status: props.event?.status || "draft",
    description:
        props.event?.description || props.event?.event_data?.description || "",
    location: props.event?.location || "",
    all_day: props.event?.all_day || false,
    recurrence: props.event?.recurrence || null,
});
for (const field of props.fields) {
    const value = form.event_data[field.field_id];
    if (typeof value !== "string" || !["date", "datetime"].includes(field.type))
        continue;
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) continue;
    form.event_data[field.field_id] =
        field.type === "date"
            ? parsed.toLocaleDateString("sv-SE", { timeZone: timezone })
            : local(value);
}
const repeat = ref(props.event?.recurrence?.frequency || ""),
    interval = ref(props.event?.recurrence?.interval || 1),
    count = ref(props.event?.recurrence?.count || 12),
    until = ref(props.event?.recurrence?.until || ""),
    weekdays = ref(
        props.event?.recurrence?.weekdays || [new Date().getDay() || 7],
    ),
    monthlyMode = ref(props.event?.recurrence?.monthly_mode || "date");
const switchTemplate = (e) => {
    const old = form.template_id;
    if (
        Object.keys(form.event_data).length &&
        !window.confirm(
            "Changing template can remove custom answers. Continue?",
        )
    ) {
        e.target.value = old;
        return;
    }
    form.template_id = e.target.value || null;
    const ids =
        props.templates.find((t) => t.id === Number(form.template_id))
            ?.fields || [];
    const keys = props.fields
        .filter((f) => ids.includes(f.id))
        .map((f) => f.field_id);
    form.event_data = Object.fromEntries(
        Object.entries(form.event_data).filter(([k]) => keys.includes(k)),
    );
    form.clearErrors();
};
watch(
    () => form.all_day,
    (value) => {
        if (value) {
            form.starts_at = form.starts_at
                ? form.starts_at.slice(0, 10) + "T00:00"
                : "";
            form.ends_at = form.ends_at
                ? form.ends_at.slice(0, 10) + "T00:00"
                : "";
            if (form.ends_at <= form.starts_at && form.starts_at) {
                const date = new Date(
                    form.starts_at.slice(0, 10) + "T12:00:00",
                );
                date.setDate(date.getDate() + 1);
                form.ends_at = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}T00:00`;
            }
        }
    },
);
const template = computed(() =>
        props.templates.find((t) => t.id === Number(form.template_id)),
    ),
    standardKeys = [
        "title",
        "name",
        "description",
        "start_date",
        "end_date",
        "timezone",
        "status",
    ],
    oldRepeatKeys = [
        "is_recurring",
        "recurring_frequency",
        "recurrence_interval",
        "recurrence_days",
        "recurrence_end_date",
        "max_occurrences",
        "exclusion_dates",
    ],
    activeFields = computed(() =>
        (template.value?.layout?.flat()?.length
            ? template.value.layout.flat()
            : template.value?.fields || []
        )
            .map((id) => props.fields.find((f) => f.id === id))
            .filter(
                (f) =>
                    f &&
                    !standardKeys.includes(f.field_id) &&
                    !(f.system_field && oldRepeatKeys.includes(f.field_id)),
            ),
    );
watch(repeat, (value) => {
    if (value === "weekly" && !props.event?.recurrence) {
        const d = new Date(form.starts_at || new Date());
        weekdays.value = [d.getDay() || 7];
    }
});
const fieldPosition = (f) => {
    const column = template.value?.layout?.[1]?.includes(f.id) ? 1 : 0;
    const list = activeFields.value.filter(
        (item) =>
            (template.value?.layout?.[1]?.includes(item.id) ? 1 : 0) === column,
    );
    return {
        "--field-column": column + 1,
        "--field-row": list.findIndex((item) => item.id === f.id) + 1,
    };
};
const inputType = (f) =>
        ({ datetime: "datetime-local", decimal: "number", image: "url" })[
            f.type
        ] || f.type,
    options = (f) => {
        if (Array.isArray(f.options)) return f.options;
        try {
            return JSON.parse(f.options || "[]");
        } catch {
            return [];
        }
    };
const setRule = () =>
    (form.recurrence = repeat.value
        ? {
              frequency: repeat.value,
              interval: Number(interval.value),
              count: Number(count.value),
              until: until.value || null,
              weekdays: weekdays.value.map(Number),
              monthly_mode: monthlyMode.value,
          }
        : null);
const preview = ref([]),
    previewError = ref("");
const previewDates = async () => {
    setRule();
    previewError.value = "";
    try {
        const { data } = await axios.post(route("events.preview"), form.data());
        preview.value = data.data;
    } catch (e) {
        preview.value = [];
        previewError.value =
            Object.values(e.response?.data?.errors || {})
                .flat()
                .join(" ") || "Could not preview this schedule.";
    }
};
const submit = () => {
    setRule();
    form[props.event?.id ? "put" : "post"](
        route(
            props.event?.id ? "events.update" : "events.store",
            props.event?.id,
        ),
        { preserveScroll: true, onSuccess: () => emit("submit") },
    );
};
</script>
<template>
    <div class="bg-white">
        <div
            class="flex items-center justify-between border-b border-slate-100 p-6"
        >
            <div class="flex items-center gap-3">
                <CalendarDays class="size-5 text-emerald-700" />
                <div>
                    <h2 class="text-lg font-semibold">
                        {{ event?.id ? "Edit event" : "Create an event" }}
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Give your next event a home in EventNest.
                    </p>
                </div>
            </div>
            <button aria-label="Close event form" @click="emit('cancel')">
                <X class="size-5 text-slate-400" />
            </button>
        </div>
        <form @submit.prevent="submit">
            <div class="max-h-[65vh] space-y-6 overflow-y-auto p-6">
                <p
                    v-if="event?.source === 'humanitix'"
                    class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900"
                >
                    Local EventNest edit. Changed fields are protected from
                    future imports. No changes are sent to Humanitix.
                </p>
                <ul
                    v-if="event?.source_snapshot?.mapping_warnings?.length"
                    class="list-inside list-disc text-sm text-amber-800"
                >
                    <li
                        v-for="warning in event.source_snapshot
                            .mapping_warnings"
                        :key="warning"
                    >
                        {{ warning }}
                    </li>
                </ul>
                <div>
                    <label for="event-title" class="workspace-label"
                        >Event title *</label
                    ><input
                        id="event-title"
                        v-model="form.title"
                        class="workspace-input"
                        required
                        maxlength="255"
                    />
                </div>
                <div>
                    <label for="event-template" class="workspace-label"
                        >Event template</label
                    ><select
                        id="event-template"
                        :value="form.template_id"
                        class="workspace-input"
                        @change="switchTemplate"
                    >
                        <option value="">Basic event — no custom fields</option>
                        <option
                            v-for="t in templates"
                            :key="t.id"
                            :value="t.id"
                        >
                            {{ t.name }}
                        </option>
                    </select>
                </div>
                <details open class="rounded-xl border border-slate-200 p-4">
                    <summary class="cursor-pointer text-sm font-medium">
                        Schedule and event details
                    </summary>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <label
                            class="flex items-center gap-2 text-sm sm:col-span-2"
                            ><input
                                v-model="form.all_day"
                                type="checkbox"
                                class="rounded text-emerald-700"
                            />All-day event (end date is exclusive)</label
                        >
                        <div>
                            <label for="event-start" class="workspace-label"
                                >Starts</label
                            ><input
                                id="event-start"
                                :value="
                                    form.all_day
                                        ? form.starts_at.slice(0, 10)
                                        : form.starts_at
                                "
                                :type="form.all_day ? 'date' : 'datetime-local'"
                                @input="
                                    form.starts_at =
                                        $event.target.value +
                                        (form.all_day ? 'T00:00' : '')
                                "
                                class="workspace-input"
                            />
                        </div>
                        <div>
                            <label for="event-end" class="workspace-label"
                                >Ends</label
                            ><input
                                id="event-end"
                                :value="
                                    form.all_day
                                        ? form.ends_at.slice(0, 10)
                                        : form.ends_at
                                "
                                :type="form.all_day ? 'date' : 'datetime-local'"
                                @input="
                                    form.ends_at =
                                        $event.target.value +
                                        (form.all_day ? 'T00:00' : '')
                                "
                                class="workspace-input"
                            />
                        </div>
                        <div>
                            <label for="event-timezone" class="workspace-label"
                                >Timezone</label
                            ><input
                                id="event-timezone"
                                v-model="form.timezone"
                                class="workspace-input"
                                placeholder="Australia/Perth"
                            />
                        </div>
                        <div>
                            <label for="event-status" class="workspace-label"
                                >Status</label
                            ><select
                                id="event-status"
                                v-model="form.status"
                                class="workspace-input"
                            >
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="event-location" class="workspace-label"
                                >Location</label
                            ><input
                                id="event-location"
                                v-model="form.location"
                                class="workspace-input"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label
                                for="event-description"
                                class="workspace-label"
                                >Description</label
                            ><textarea
                                id="event-description"
                                v-model="form.description"
                                class="workspace-input"
                                rows="3"
                            />
                        </div>
                    </div>
                </details>
                <fieldset
                    class="space-y-4 rounded-xl border border-slate-200 p-4"
                >
                    <legend class="px-1 text-sm font-medium">Repeat</legend>
                    <label for="repeat-frequency" class="workspace-label"
                        >Frequency</label
                    >
                    <select
                        id="repeat-frequency"
                        v-model="repeat"
                        class="workspace-input"
                    >
                        <option value="">Does not repeat</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                    <div v-if="repeat" class="space-y-4">
                        <p class="text-xs text-slate-500">
                            Repeats at the same local time in
                            {{ form.timezone }}. Invalid month-end or leap dates
                            are skipped.
                        </p>
                        <label class="workspace-label"
                            >Every N periods<input
                                v-model="interval"
                                type="number"
                                min="1"
                                max="100"
                                required
                                class="workspace-input"
                        /></label>
                        <div
                            v-if="repeat === 'weekly'"
                            class="flex flex-wrap gap-3"
                        >
                            <label
                                v-for="(day, i) in [
                                    'Mon',
                                    'Tue',
                                    'Wed',
                                    'Thu',
                                    'Fri',
                                    'Sat',
                                    'Sun',
                                ]"
                                :key="day"
                                class="text-sm"
                                ><input
                                    v-model="weekdays"
                                    type="checkbox"
                                    :value="i + 1"
                                />
                                {{ day }}</label
                            >
                        </div>
                        <label
                            v-if="repeat === 'monthly'"
                            class="workspace-label"
                            >Monthly pattern<select
                                v-model="monthlyMode"
                                class="workspace-input"
                            >
                                <option value="date">Same day of month</option>
                                <option value="weekday">
                                    Same weekday position (for example, second
                                    Tuesday)
                                </option>
                            </select></label
                        >
                        <label class="workspace-label"
                            >Maximum occurrences<input
                                v-model="count"
                                type="number"
                                min="1"
                                max="1000"
                                required
                                class="workspace-input"
                        /></label>
                        <label class="workspace-label"
                            >Until date (optional)<input
                                v-model="until"
                                type="date"
                                class="workspace-input"
                        /></label>
                        <button
                            type="button"
                            class="workspace-secondary"
                            @click="previewDates"
                        >
                            Preview next occurrences
                        </button>
                        <p
                            v-if="previewError"
                            role="alert"
                            class="text-sm text-red-700"
                        >
                            {{ previewError }}
                        </p>
                        <ol
                            v-if="preview.length"
                            class="list-inside list-decimal text-sm text-slate-600"
                        >
                            <li v-for="p in preview" :key="p.id">
                                {{
                                    new Date(p.starts_at).toLocaleString(
                                        undefined,
                                        { timeZone: form.timezone },
                                    )
                                }}
                                · {{ form.timezone }}
                            </li>
                        </ol>
                        <p class="text-sm text-emerald-800">
                            {{ repeat }} · every {{ interval }} · up to
                            {{ count }} occurrences{{
                                until ? `, ending by ${until}` : ""
                            }}
                        </p>
                    </div>
                </fieldset>
                <p
                    v-if="
                        event &&
                        !event.starts_at &&
                        event.event_data?.start_date
                    "
                    role="status"
                    class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900"
                >
                    This event has a legacy custom date. Confirm its start, end
                    and timezone above before adding it to the calendar.
                    Original custom data is preserved.
                </p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div
                        v-for="f in activeFields"
                        :key="f.id"
                        class="event-custom-field"
                        :style="fieldPosition(f)"
                    >
                        <label
                            :for="`event-field-${f.id}`"
                            class="workspace-label"
                            >{{ f.label }}{{ f.required ? " *" : "" }}</label
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
                            <option value="">Choose an option</option>
                            <option
                                v-for="option in options(f)"
                                :key="option"
                                :value="option"
                            >
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
                <p
                    v-for="(error, key) in form.errors"
                    :key="key"
                    role="alert"
                    class="text-sm text-red-700"
                >
                    {{ error }}
                </p>
            </div>
            <div
                class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-5"
            >
                <button
                    type="button"
                    class="workspace-secondary"
                    @click="emit('cancel')"
                >
                    Cancel</button
                ><button class="workspace-primary" :disabled="form.processing">
                    {{
                        form.processing
                            ? "Saving…"
                            : event?.id
                              ? "Save changes"
                              : "Create event"
                    }}
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
@media (min-width: 640px) {
    .event-custom-field {
        grid-column: var(--field-column);
        grid-row: var(--field-row);
    }
}
</style>
