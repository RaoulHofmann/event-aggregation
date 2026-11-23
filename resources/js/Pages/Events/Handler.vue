<script setup>
import { computed, nextTick, ref, watch } from "vue";
import EditorChrome from "@/Components/EditorChrome.vue";
import WorkspaceImages from "@/Components/WorkspaceImages.vue";
import axios from "axios";
import { Link, useForm } from "@inertiajs/vue3";
import { CalendarDays, X, ArrowRight, Layers } from "lucide-vue-next";
const section = ref("details");
const sections = [
    { id: "details", label: "Event details" },
    { id: "schedule", label: "Schedule & repeat" },
    { id: "custom", label: "Custom fields" },
];
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
    template_id: props.templates.some((t) => t.id === props.event?.template_id)
        ? props.event.template_id
        : "",
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
    cover_image_id: props.event?.cover_image_id || null,
});
const showImages = ref(false);
const uploadingImage = ref(false);
const imagePickerButton = ref(null);
async function chooseImage(image) {
    form.cover_image_id = image.id;
    form.clearErrors("cover_image_id");
    showImages.value = false;
    await nextTick();
    imagePickerButton.value?.focus();
}
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
const fieldColumns = computed(() =>
    [0, 1].map((column) =>
        activeFields.value.filter(
            (f) =>
                (template.value?.layout?.[1]?.includes(f.id) ? 1 : 0) ===
                column,
        ),
    ),
);
const hasBothColumns = computed(() =>
    fieldColumns.value.every((column) => column.length),
);
const scheduleSummary = computed(() =>
    form.starts_at ? form.starts_at.replace("T", " at ") : "No date set",
);
const showErrors = (errors) => {
    section.value = errors.title
        ? "details"
        : Object.keys(errors).some((k) => k.startsWith("event_data."))
          ? "custom"
          : Object.keys(errors).some((k) =>
                  [
                      "starts_at",
                      "ends_at",
                      "timezone",
                      "recurrence",
                      "all_day",
                  ].some((name) => k.startsWith(name)),
              )
            ? "schedule"
            : "details";
};
const inputType = (f) =>
        ({
            datetime: "datetime-local",
            decimal: "number",
            image: "url",
            file: "url",
        })[f.type] || f.type,
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
        const { data } = await axios.post(route("events.preview"), {
            ...form.data(),
            title: form.title || "Schedule preview",
        });
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
    if (uploadingImage.value || form.processing) return;
    if (!form.title.trim()) {
        form.setError("title", "Enter an event title.");
        section.value = "details";
        return;
    }
    setRule();
    form[props.event?.id ? "put" : "post"](
        route(
            props.event?.id ? "events.update" : "events.store",
            props.event?.id,
        ),
        {
            preserveScroll: true,
            onSuccess: () => emit("submit"),
            onError: showErrors,
        },
    );
};
</script>
<template>
    <div class="event-editor">
        <EditorChrome
            context="Events"
            :name="template?.name || 'Basic event'"
            :title="event?.id ? 'Edit event' : 'Create an event'"
            close-label="Close event form"
            @close="emit('cancel')"
        />
        <form novalidate @submit.prevent="submit">
            <div class="editor-workspace">
                <aside class="editor-sidebar">
                    <section>
                        <label for="event-template" class="workspace-label"
                            >Event template</label
                        ><select
                            id="event-template"
                            :value="form.template_id"
                            class="workspace-input"
                            @change="switchTemplate"
                        >
                            <option value="">
                                Basic event — no custom fields
                            </option>
                            <option
                                v-for="t in templates"
                                :key="t.id"
                                :value="t.id"
                            >
                                {{ t.name }}
                            </option>
                        </select>
                        <p v-if="template?.description" class="template-description">{{ template.description }}</p>
                    </section>
                    <section>
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
                        <p v-if="form.status === 'published'" class="template-description">
                            A start date and timezone are required.
                        </p>
                    </section>
                    <section class="editor-summary">
                        <p class="editor-eyebrow">AT A GLANCE</p>
                        <strong v-if="form.title">{{ form.title }}</strong>
                        <p>{{ scheduleSummary }}</p>
                        <p>{{ form.timezone }}</p>
                        <p>
                            {{ repeat ? "Repeats " + repeat : "One-off event" }}
                        </p>
                    </section>

                    <p
                        v-if="
                            event &&
                            !event.starts_at &&
                            event.event_data?.start_date
                        "
                        class="import-note"
                    >
                        This event has a legacy custom date. Confirm its
                        schedule and timezone before adding it to the calendar.
                    </p>
                </aside>
                <div class="editor-main">
                    <nav class="editor-tabs" aria-label="Event form sections">
                        <button
                            v-for="tab in sections"
                            :key="tab.id"
                            type="button"
                            :aria-pressed="section === tab.id"
                            :aria-controls="`event-section-${tab.id}`"
                            @click="section = tab.id"
                        >
                            {{ tab.label
                            }}<span v-if="tab.id === 'custom'">{{
                                activeFields.length
                            }}</span>
                        </button>
                    </nav>
                    <div class="editor-content">
                        <section
                            v-show="section === 'details'"
                            id="event-section-details"
                            class="editor-section"
                        >
                            <div class="section-heading">
                                <h3>Event details</h3>
                            </div>
                            <div>
                                <label for="event-title" class="workspace-label"
                                    >Event title *</label
                                ><input
                                    id="event-title"
                                    v-model="form.title"
                                    class="workspace-input event-title-input"
                                    maxlength="255"
                                    placeholder="Give your event a name"
                                />
                            </div>
                            <div>
                                <label
                                    for="event-description"
                                    class="workspace-label"
                                    >Description</label
                                ><textarea
                                    id="event-description"
                                    v-model="form.description"
                                    class="workspace-input"
                                    rows="6"
                                    placeholder="What can people expect?"
                                />
                            </div>
                            <div>
                                <label
                                    for="event-location"
                                    class="workspace-label"
                                    >Location</label
                                ><input
                                    id="event-location"
                                    v-model="form.location"
                                    class="workspace-input"
                                    placeholder="Venue, address or online location"
                                />
                            </div>
                            <fieldset class="space-y-3 rounded-xl border border-slate-200 p-4">
                                <legend class="px-1 text-sm font-semibold">Cover image</legend>
                                <p class="text-xs text-slate-600">Published covers can be viewed by anyone with the image link.</p>
                                <img
                                    v-if="form.cover_image_id"
                                    :src="route('assets.images.show', form.cover_image_id)"
                                    alt="Selected event cover"
                                    class="max-h-48 w-full rounded-lg bg-slate-50 object-contain sm:max-w-xs"
                                />
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        ref="imagePickerButton"
                                        type="button"
                                        class="workspace-secondary"
                                        :aria-expanded="showImages"
                                        aria-controls="event-image-picker"
                                        :disabled="uploadingImage"
                                        @click="showImages = !showImages"
                                    >{{ showImages ? 'Close image picker' : 'Upload or choose image' }}</button>
                                    <button
                                        v-if="form.cover_image_id"
                                        type="button"
                                        class="workspace-secondary"
                                        :disabled="uploadingImage"
                                        @click="form.cover_image_id = null"
                                    >Remove cover</button>
                                </div>
                                <div v-if="showImages" id="event-image-picker" class="border-t border-slate-200 pt-3">
                                    <WorkspaceImages selectable @select="chooseImage" @uploading="uploadingImage = $event" />
                                </div>
                            </fieldset>
                            <button
                                type="button"
                                class="workspace-secondary"
                                @click="section = 'schedule'"
                            >
                                Next: schedule & repeat
                                <ArrowRight class="size-4" />
                            </button>
                        </section>
                        <section
                            v-show="section === 'schedule'"
                            id="event-section-schedule"
                            class="editor-section"
                        >
                            <div class="section-heading">
                                <h3>Schedule &amp; repeat</h3>
                            </div>
                            <label class="all-day-toggle"
                                ><input
                                    v-model="form.all_day"
                                    type="checkbox"
                                />All-day event<span v-if="form.all_day"
                                    >End date is exclusive.</span
                                ></label
                            >
                            <div class="schedule-grid">
                                <div>
                                    <label
                                        for="event-start"
                                        class="workspace-label"
                                        >Starts</label
                                    ><input
                                        id="event-start"
                                        :value="
                                            form.all_day
                                                ? form.starts_at.slice(0, 10)
                                                : form.starts_at
                                        "
                                        :type="
                                            form.all_day
                                                ? 'date'
                                                : 'datetime-local'
                                        "
                                        class="workspace-input"
                                        @input="
                                            form.starts_at =
                                                $event.target.value +
                                                (form.all_day ? 'T00:00' : '')
                                        "
                                    />
                                </div>
                                <div>
                                    <label
                                        for="event-end"
                                        class="workspace-label"
                                        >Ends</label
                                    ><input
                                        id="event-end"
                                        :value="
                                            form.all_day
                                                ? form.ends_at.slice(0, 10)
                                                : form.ends_at
                                        "
                                        :type="
                                            form.all_day
                                                ? 'date'
                                                : 'datetime-local'
                                        "
                                        class="workspace-input"
                                        @input="
                                            form.ends_at =
                                                $event.target.value +
                                                (form.all_day ? 'T00:00' : '')
                                        "
                                    />
                                </div>
                            </div>
                            <div>
                                <label
                                    for="event-timezone"
                                    class="workspace-label"
                                    >Timezone</label
                                ><input
                                    id="event-timezone"
                                    v-model="form.timezone"
                                    class="workspace-input"
                                    placeholder="Australia/Perth"
                                />
                            </div>
                            <fieldset class="repeat-settings">
                                <legend>Repeat settings</legend>
                                <label
                                    for="repeat-frequency"
                                    class="workspace-label"
                                    >Frequency</label
                                ><select
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
                                <div v-if="repeat" class="repeat-controls">
                                    <p>
                                        Repeats at the same local time in
                                        {{ form.timezone }}. Invalid month-end
                                        or leap dates are skipped.
                                    </p>
                                    <div class="schedule-grid">
                                        <label class="workspace-label"
                                            >Every N periods<input
                                                v-model="interval"
                                                type="number"
                                                min="1"
                                                max="100"
                                                class="workspace-input" /></label
                                        ><label class="workspace-label"
                                            >Maximum occurrences<input
                                                v-model="count"
                                                type="number"
                                                min="1"
                                                max="1000"
                                                class="workspace-input"
                                        /></label>
                                    </div>
                                    <fieldset
                                        v-if="repeat === 'weekly'"
                                        class="weekday-options"
                                    >
                                        <legend>Repeat on</legend>
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
                                            ><input
                                                v-model="weekdays"
                                                type="checkbox"
                                                :value="i + 1"
                                            />{{ day }}</label
                                        >
                                    </fieldset>
                                    <label
                                        v-if="repeat === 'monthly'"
                                        class="workspace-label"
                                        >Monthly pattern<select
                                            v-model="monthlyMode"
                                            class="workspace-input"
                                        >
                                            <option value="date">
                                                Same day of month
                                            </option>
                                            <option value="weekday">
                                                Same weekday position (for
                                                example, second Tuesday)
                                            </option>
                                        </select></label
                                    ><label class="workspace-label"
                                        >Until date (optional)<input
                                            v-model="until"
                                            type="date"
                                            class="workspace-input" /></label
                                    ><button
                                        type="button"
                                        class="workspace-secondary"
                                        @click="previewDates"
                                    >
                                        Preview next occurrences
                                    </button>
                                    <p
                                        v-if="previewError"
                                        role="alert"
                                        class="editor-error"
                                    >
                                        {{ previewError }}
                                    </p>
                                    <ol
                                        v-if="preview.length"
                                        class="occurrence-preview"
                                    >
                                        <li v-for="p in preview" :key="p.id">
                                            {{
                                                new Date(
                                                    p.starts_at,
                                                ).toLocaleString(undefined, {
                                                    timeZone: form.timezone,
                                                })
                                            }}
                                            · {{ form.timezone }}
                                        </li>
                                    </ol>
                                </div>
                            </fieldset>
                            <button
                                type="button"
                                class="workspace-secondary"
                                @click="section = 'custom'"
                            >
                                Next: custom fields
                                <ArrowRight class="size-4" />
                            </button>
                        </section>
                        <section
                            v-show="section === 'custom'"
                            id="event-section-custom"
                            class="editor-section"
                        >
                            <div class="section-heading">
                                <h3>Custom fields</h3>
                            </div>
                            <ul
                                v-if="
                                    event?.source_snapshot?.mapping_warnings
                                        ?.length &&
                                    !event?.local_overrides?.includes(
                                        'event_data',
                                    )
                                "
                                class="mapping-notes"
                            >
                                <li
                                    v-for="warning in event.source_snapshot
                                        .mapping_warnings"
                                    :key="warning"
                                >
                                    {{ warning }}
                                </li>
                            </ul>
                            <div
                                v-if="!activeFields.length"
                                class="no-custom-fields"
                            >
                                <Layers class="size-8" />
                                <h4>No custom fields</h4>
                                <Link :href="route('event-templates.index')"
                                    >Manage event templates
                                    <ArrowRight class="size-4"
                                /></Link>
                            </div>
                            <div
                                v-else
                                class="template-field-columns"
                                :class="{ 'two-columns': hasBothColumns }"
                            >
                                <div
                                    v-for="(column, i) in fieldColumns.filter(
                                        (c) => c.length,
                                    )"
                                    :key="i"
                                    class="template-field-column"
                                >
                                    <div
                                        v-for="f in column"
                                        :key="f.id"
                                        @input="
                                            form.clearErrors(
                                                `event_data.${f.field_id}`,
                                            )
                                        "
                                    >
                                        <label
                                            :for="`event-field-${f.id}`"
                                            class="workspace-label"
                                            >{{ f.label
                                            }}{{
                                                ["image", "file"].includes(
                                                    f.type,
                                                )
                                                    ? " URL"
                                                    : ""
                                            }}{{
                                                f.required ? " *" : ""
                                            }}</label
                                        ><textarea
                                            v-if="f.type === 'textarea'"
                                            :id="`event-field-${f.id}`"
                                            v-model="
                                                form.event_data[f.field_id]
                                            "
                                            class="workspace-input"
                                            rows="5"
                                        /><select
                                            v-else-if="f.type === 'select'"
                                            :id="`event-field-${f.id}`"
                                            v-model="
                                                form.event_data[f.field_id]
                                            "
                                            class="workspace-input"
                                        >
                                            <option value="">
                                                Choose an option
                                            </option>
                                            <option
                                                v-for="option in options(f)"
                                                :key="option"
                                                :value="option"
                                            >
                                                {{ option }}
                                            </option></select
                                        ><label
                                            v-else-if="
                                                [
                                                    'boolean',
                                                    'checkbox',
                                                ].includes(f.type)
                                            "
                                            class="boolean-field"
                                            ><input
                                                :id="`event-field-${f.id}`"
                                                v-model="
                                                    form.event_data[f.field_id]
                                                "
                                                type="checkbox"
                                            />Yes</label
                                        ><input
                                            v-else
                                            :id="`event-field-${f.id}`"
                                            v-model="
                                                form.event_data[f.field_id]
                                            "
                                            :type="inputType(f)"
                                            :step="
                                                f.type === 'decimal'
                                                    ? 'any'
                                                    : undefined
                                            "
                                            class="workspace-input"
                                        />
                                        <p
                                            v-if="
                                                ['image', 'file'].includes(
                                                    f.type,
                                                )
                                            "
                                            class="field-help"
                                        >
                                            Link to a hosted image.
                                        </p>
                                        <p
                                            v-if="
                                                form.errors[
                                                    `event_data.${f.field_id}`
                                                ]
                                            "
                                            role="alert"
                                            class="editor-error"
                                        >
                                            {{
                                                form.errors[
                                                    `event_data.${f.field_id}`
                                                ]
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <div
                            v-if="Object.keys(form.errors).length"
                            class="validation-summary"
                            role="alert"
                        >
                            <strong>Check these details before saving:</strong>
                            <p v-for="(error, key) in form.errors" :key="key">
                                {{ error }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="editor-footer">
                <div>
                    <button
                        type="button"
                        class="workspace-secondary"
                        @click="emit('cancel')"
                    >
                        Cancel</button
                    ><button
                        class="workspace-primary"
                        :disabled="form.processing || uploadingImage"
                    >
                        {{
                            form.processing
                                ? "Saving…"
                                : event?.id
                                  ? "Save changes"
                                  : form.status === "published"
                                    ? "Publish event"
                                    : "Create event"
                        }}
                    </button>
                </div>
            </footer>
        </form>
    </div>
</template>
<style scoped>
.event-editor {
    background: #fff4e4;
    color: #27231f;
    font-family: Arial, Helvetica, sans-serif;
}
.event-editor .workspace-primary {
    background: #3b3029;
}
.event-editor .workspace-primary:hover {
    background: #7d392b;
}
.editor-header {
    display: flex;
    justify-content: space-between;
    gap: 24px;
    align-items: flex-start;
    padding: 24px 30px;
    border-bottom: 2px solid #27231f;
}
.editor-eyebrow {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.1em;
    margin: 0 0 10px;
}
.editor-header h2 {
    font-size: 28px;
    font-weight: 900;
    letter-spacing: -0.9px;
    line-height: 1.2;
    margin: 0 0 8px;
}
.editor-header > div > p:last-child {
    font-size: 15px;
    line-height: 1.6;
    margin: 0;
    color: #66533d;
}
.close-editor {
    padding: 8px;
}
.editor-workspace {
    display: grid;
    grid-template-columns: 290px minmax(0, 1fr);
    max-height: calc(100dvh - 250px);
    min-height: min(460px, calc(100dvh - 250px));
    overflow-y: auto;
}
.editor-sidebar {
    border-right: 1px solid #c7b596;
    padding: 25px;
    background: #f3e2c8;
    display: flex;
    flex-direction: column;
    gap: 28px;
    align-self: stretch;
}
.template-description {
    font-size: 14px;
    line-height: 1.8;
    color: #66533d;
    margin-top: 14px;
}
.editor-summary {
    border-top: 2px solid #27231f;
    padding-top: 20px;
}
.editor-summary > strong {
    font-size: 20px;
    line-height: 1.3;
    display: block;
    margin-bottom: 16px;
    overflow-wrap: anywhere;
}
.editor-summary > p:not(.editor-eyebrow) {
    font-size: 14px;
    line-height: 1.8;
    margin: 5px 0;
    overflow-wrap: anywhere;
}
.import-note {
    font-size: 14px;
    line-height: 1.8;
    border: 1px solid #a7834d;
    background: #f5d494;
    padding: 15px;
}
.editor-main {
    min-width: 0;
}
.editor-tabs {
    display: flex;
    gap: 0;
    border-bottom: 1px solid #c7b596;
    background: #fff4e4;
    position: sticky;
    top: 0;
    z-index: 1;
}
.editor-tabs button {
    padding: 20px;
    font-size: 15px;
    font-weight: 700;
    border-right: 1px solid #c7b596;
    border-bottom: 3px solid transparent;
    flex: 1;
    white-space: nowrap;
}
.editor-tabs button[aria-pressed="true"] {
    background: #f5be28;
    border-bottom-color: #27231f;
}
.editor-tabs span {
    display: inline-block;
    font-size: 11px;
    margin-left: 7px;
}
.editor-content {
    padding: 30px;
}
.editor-section {
    display: flex;
    flex-direction: column;
    gap: 26px;
}
.editor-section[style*="display: none"] {
    display: none !important;
}
.section-heading h3 {
    font-size: 27px;
    line-height: 1.2;
    letter-spacing: -0.9px;
    font-weight: 900;
    margin: 0 0 12px;
}
.event-title-input {
    font-size: 20px;
}
.editor-section > .workspace-secondary {
    align-self: flex-start;
}
.schedule-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.all-day-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 16px;
}
.all-day-toggle > span {
    font-size: 13px;
    color: #66533d;
}
.repeat-settings {
    border: 1px solid #ad9878;
    padding: 20px;
}
.repeat-settings > legend {
    font-weight: 700;
    font-size: 16px;
    padding: 0 8px;
}
.repeat-controls {
    display: flex;
    flex-direction: column;
    gap: 22px;
    margin-top: 20px;
}
.repeat-controls > p {
    font-size: 14px;
    line-height: 1.8;
    color: #66533d;
}
.weekday-options {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
.weekday-options legend {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 12px;
}
.weekday-options label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 14px;
}
.occurrence-preview {
    list-style: decimal;
    padding-left: 22px;
    font-size: 15px;
    line-height: 1.9;
}
.template-field-columns {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 28px;
}
.template-field-columns.two-columns {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
}
.template-field-column {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 26px;
}
.field-help {
    font-size: 13px;
    line-height: 1.7;
    margin-top: 8px;
    color: #66533d;
}
.boolean-field {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
}
.no-custom-fields {
    padding: 28px;
    border: 1px dashed #a7834d;
    background: #f7e8d0;
}
.no-custom-fields h4 {
    font-size: 21px;
    line-height: 1.3;
    font-weight: 800;
    margin: 18px 0 15px;
}
.no-custom-fields a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: underline;
    margin-top: 20px;
}
.mapping-notes {
    font-size: 14px;
    line-height: 1.8;
    color: #8b4017;
    padding: 15px;
    background: #f5dfb0;
}
.editor-error {
    font-size: 14px;
    color: #a42c19;
    line-height: 1.7;
    margin-top: 8px;
}
.validation-summary {
    margin-top: 25px;
    padding: 18px;
    border: 1px solid #b5331e;
    color: #9d2d1c;
    font-size: 14px;
    line-height: 1.8;
}
.editor-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 20px;
    border-top: 2px solid #27231f;
    padding: 18px 30px;
    background: #fff4e4;
}
.editor-footer > div {
    display: flex;
    gap: 12px;
}
.editor-workspace input[type="checkbox"] {
    accent-color: #b5331e;
    color: #b5331e;
    border-radius: 2px;
}
@media (min-width: 1000px) {
    .editor-sidebar {
        position: sticky;
        top: 0;
        align-self: start;
        min-height: 100%;
    }
}
@media (max-width: 800px) {
    .editor-workspace {
        grid-template-columns: 1fr;
        max-height: calc(100dvh - 230px);
        min-height: 0;
    }
    .editor-sidebar {
        border-right: 0;
        border-bottom: 1px solid #c7b596;
        padding: 20px;
        gap: 20px;
    }
    .editor-summary {
        display: none;
    }
    .editor-main {
        overflow: visible;
    }
    .editor-content {
        padding: 25px 20px;
    }
    .editor-tabs button {
        padding: 16px 8px;
        font-size: 13px;
    }
    .editor-header {
        padding: 20px;
    }
    .editor-header h2 {
        font-size: 25px;
    }
    .editor-header > div > p:last-child {
        font-size: 14px;
    }
    .editor-footer {
        padding: 15px 20px;
    }
    .editor-footer > div {
        width: 100%;
        justify-content: flex-end;
    }
    .template-field-columns.two-columns,
    .schedule-grid {
        grid-template-columns: 1fr;
    }
    .all-day-toggle {
        flex-wrap: wrap;
    }
    .editor-section {
        gap: 22px;
    }
    .section-heading h3 {
        font-size: 25px;
    }
}
</style>

<style scoped>
.event-editor {
    background: #fff;
    color: #29313b;
    font-family: inherit;
}
.editor-header {
    padding: 22px 30px;
    border-bottom: 1px solid #e3e6ea;
    background: #fff;
}
.editor-header h2 {
    font-size: 25px;
    font-weight: 650;
    letter-spacing: -0.5px;
}
.editor-header > div > p:last-child {
    color: #657184;
    font-size: 14px;
}
.editor-eyebrow {
    font-weight: 600;
    letter-spacing: 0.07em;
    color: #737f8d;
    font-size: 10px;
}
.editor-sidebar {
    background: #f8f9fa;
    border-color: #e3e6ea;
}
.template-description,
.editor-summary > p:not(.editor-eyebrow) {
    color: #657184;
}
.editor-summary {
    border-top: 1px solid #e1e5e9;
}
.editor-summary > strong {
    font-size: 18px;
    font-weight: 600;
}
.editor-tabs {
    background: #fff;
    border-color: #e3e6ea;
}
.editor-tabs button {
    border-right: 0;
    padding: 18px 16px;
    color: #687486;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 2px solid transparent;
}
.editor-tabs button[aria-pressed="true"] {
    background: #faf8f4;
    color: #806048;
    border-bottom-color: #ad8b6b;
}
.section-heading h3 {
    font-size: 23px;
    font-weight: 600;
    letter-spacing: -0.4px;
}
.editor-section {
    gap: 23px;
}
.event-title-input {
    font-size: 17px;
}
.editor-content {
    padding: 28px 30px;
}
.editor-footer {
    background: #fff;
    border-top: 1px solid #e3e6ea;
}
.repeat-settings {
    border-color: #e1e5e9;
    border-radius: 8px;
}
.repeat-settings > legend {
    font-size: 14px;
    font-weight: 600;
}
.event-editor .workspace-label {
    font-size: 14px;
    font-weight: 500;
    color: #536173;
}
.event-editor .workspace-input {
    border-color: #d7dde5;
    border-radius: 6px;
    box-shadow: 0 1px 2px #29313b05;
}
.event-editor .workspace-primary {
    font-weight: 600;
    background: #4e443b;
}
.event-editor .workspace-secondary {
    font-weight: 500;
}
.no-custom-fields {
    background: #fafbfc;
    border-color: #d9dfe6;
    border-radius: 8px;
}
.no-custom-fields h4 {
    font-weight: 600;
    font-size: 19px;
}
.import-note {
    background: #fcf7ed;
    border-color: #e3d8c3;
    border-radius: 6px;
    color: #72634e;
}
.editor-workspace input[type="checkbox"] {
    accent-color: #8b7159;
    color: #8b7159;
}
.editor-error,
.validation-summary {
    color: #a43d30;
}
.validation-summary {
    border-radius: 6px;
    border-color: #e0b9af;
    background: #fffaf8;
}
.field-help,
.repeat-controls > p {
    color: #657184;
}
@media (max-width: 800px) {
    .editor-content {
        padding: 24px 20px;
    }
    .editor-tabs button {
        font-size: 12px;
        padding: 16px 8px;
    }
    .editor-header {
        padding: 20px;
    }
    .editor-header h2 {
        font-size: 23px;
    }
}
</style>

<style scoped>
.event-editor {
    background: #fffdf9;
    color: #39323e;
}
.editor-sidebar {
    background: #fcf7e9;
    border-color: #e5dfeb;
}
.editor-main,
.editor-content {
    background: #fffdf9;
}
.editor-tabs {
    background: #faf8fc;
    border-color: #e5dfeb;
}
.editor-tabs button {
    color: #8a7a96;
    border-bottom-color: transparent;
}
.editor-tabs button[aria-pressed="true"] {
    background: #4f435b;
    border-color: #4f435b;
    color: #fff;
}
.editor-tabs span {
    color: inherit;
}
.event-editor .workspace-input {
    background: #fff;
    border-color: #e0d7e7;
    border-radius: 5px;
    box-shadow: 0 1px 2px #40344f03;
}
.event-editor .workspace-input:focus {
    border-color: #a48bae;
    --tw-ring-color: #a48bae;
}
.event-editor .workspace-label {
    color: #655a70;
}
.editor-summary {
    background: #f7edce;
    border: 1px solid #e6d8b3;
    border-radius: 6px;
    padding: 18px;
}
.editor-summary > strong {
    font-size: 21px;
    letter-spacing: -0.5px;
    font-weight: 700;
    line-height: 1.2;
}
.editor-summary .editor-eyebrow {
    color: #9a865b;
}
.editor-summary > p:not(.editor-eyebrow) {
    color: #8b7957;
    font-size: 12px;
}
.editor-eyebrow {
    color: #96849f;
}
.section-heading h3 {
    font-size: 24px;
    font-weight: 650;
    letter-spacing: -0.5px;
    color: #42354c;
}
.template-description {
    color: #8d7d98;
}
.template-field-column {
    border: 1px solid #e8e0ed;
    border-radius: 6px;
    padding: 22px;
    background: #fff;
}
.template-field-columns {
    gap: 22px;
}
.editor-footer {
    background: #faf8fc;
    border-color: #e5dfeb;
}
.event-editor .workspace-primary {
    background: #4f435b;
    font-weight: 600;
}
.event-editor .workspace-primary:hover {
    background: #665574;
}
.repeat-settings {
    border-color: #e5dfeb;
    background: white;
}
.no-custom-fields {
    background: #f7f3fa;
    border-color: #e0d4e8;
}
.editor-workspace input[type="checkbox"] {
    color: #7e658d;
    accent-color: #7e658d;
}
@media (max-width: 800px) {
    .template-field-column {
        padding: 18px;
    }
    .editor-sidebar {
        border-color: #e5dfeb;
    }
}
</style>

<style scoped>
.editor-workspace {
    max-height: calc(100dvh - 260px);
    min-height: min(470px, calc(100dvh - 260px));
}
@media (max-width: 800px) {
    .editor-workspace {
        max-height: calc(100dvh - 280px);
        min-height: 0;
    }
}
</style>
