<?php

namespace App\Http\Controllers;

use App\Integrations\Inbound\InboundSources;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Services\EventOccurrences;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EventController extends Controller
{
    private const BULK_LIMIT = 500;

    private const PUBLISH_RULES = ['title' => ['required', 'string'], 'starts_at' => ['required', 'date'], 'timezone' => ['required', 'timezone']];

    private const CUSTOM_ALIASES = ['title' => 'title', 'name' => 'title', 'description' => 'description', 'start_date' => 'starts_at', 'end_date' => 'ends_at', 'timezone' => 'timezone', 'status' => 'status'];

    public function index(Request $request)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255'], 'source' => ['nullable', 'in:'.implode(',', InboundSources::EVENT_SOURCES)], 'template' => ['nullable', 'integer'], 'status' => ['nullable', 'in:draft,published,cancelled'], 'undated' => ['nullable', 'boolean']]);
        $query = Event::latest()->orderByDesc('id')->when($filters['search'] ?? null, fn ($q, $v) => $q->where('title', 'ilike', '%'.$v.'%'))
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['template'] ?? null, fn ($q, $v) => $q->where('template_id', $v))
            ->when($request->boolean('undated'), fn ($q) => $q->whereNull('starts_at'));

        return Inertia::render('Events/Index', ['events' => $query->paginate(50)->withQueryString(), 'eventTemplates' => EventTemplate::orderBy('name')->get(), 'fieldTemplates' => FieldTemplate::orderBy('label')->get(), 'openCreate' => $request->boolean('create'), 'filters' => $filters, 'undatedCount' => Event::whereNull('starts_at')->count(), 'bulkLimit' => self::BULK_LIMIT]);
    }

    public function occurrences(Request $request, EventOccurrences $occurrences)
    {
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after:from'], 'search' => ['nullable', 'string', 'max:255'], 'source' => ['nullable', 'in:'.implode(',', InboundSources::EVENT_SOURCES)], 'template' => ['nullable', 'integer'], 'status' => ['nullable', 'in:draft,published,cancelled']]);
        $from = CarbonImmutable::parse($data['from'])->utc();
        $to = CarbonImmutable::parse($data['to'])->utc();
        abort_if($from->diffInDays($to) > 93, 422, 'Choose a calendar window of 93 days or fewer.');

        return response()->json(['data' => $occurrences->window($from, $to, $data)]);
    }

    public function exception(Request $request, Event $event)
    {
        abort_unless($event->recurrence, 403);
        $data = $request->validate(['occurrence_date' => ['required', 'date_format:Y-m-d'], 'cancelled' => ['required', 'boolean'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at']]);
        if (! $data['cancelled']) {
            $request->validate(['starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after_or_equal:starts_at']]);
        }
        $day = CarbonImmutable::parse($data['occurrence_date'], $event->timezone ?: 'UTC');
        $items = app(EventOccurrences::class)->forEvent($event, $day->utc(), $day->addDay()->utc(), true);
        $valid = collect($items)->contains('occurrence_date', $data['occurrence_date']);
        abort_unless($valid, 422, 'This date is not an occurrence of the series.');
        $zone = $event->timezone ?: 'UTC';
        if ($event->all_day && ! $data['cancelled']) {
            foreach (['starts_at', 'ends_at'] as $key) {
                if (CarbonImmutable::parse($data[$key], $zone)->setTimezone($zone)->format('H:i:s') !== '00:00:00') {
                    throw ValidationException::withMessages([$key => 'All-day occurrence dates must be at midnight.']);
                }
            }
            $request->validate(['ends_at' => ['after:starts_at']]);
        }
        abort_if(! $data['cancelled'] && empty($data['starts_at']), 422, 'Choose a new start for this occurrence.');
        DB::table('event_exceptions')->updateOrInsert(['event_id' => $event->id, 'occurrence_date' => $data['occurrence_date']], ['cancelled' => $data['cancelled'], 'starts_at' => ! empty($data['starts_at']) ? CarbonImmutable::parse($data['starts_at'], $zone)->utc() : null, 'ends_at' => ! empty($data['ends_at']) ? CarbonImmutable::parse($data['ends_at'], $zone)->utc() : null, 'updated_at' => now(), 'created_at' => now()]);

        return back()->with('success', 'Occurrence updated.');
    }

    public function preview(Request $request, EventOccurrences $occurrences)
    {
        $data = $this->data($request, false);
        $event = (new Event($data))->forceFill($this->canonical($request, $data['event_data']));
        $start = $event->starts_at ? CarbonImmutable::instance($event->starts_at) : now()->toImmutable();

        return response()->json(['data' => array_slice($occurrences->forEvent($event, $start->subDay(), $start->addYears(10), true), 0, 8)]);
    }

    public function show(Event $event)
    {
        return response()->json($event);
    }

    public function apiShow(Event $event): EventResource
    {
        return new EventResource($event->load('template'));
    }

    public function get(Request $request)
    {
        $filters = $request->validate(['template_id' => ['nullable', 'integer', 'min:1'], 'template_name' => ['nullable', 'string', 'max:255'], 'limit' => ['nullable', 'integer', 'min:1', 'max:1000']]);
        $events = Event::with('template:id,name')->latest()
            ->when($filters['template_id'] ?? null, fn ($q, $v) => $q->where('template_id', $v))
            ->when($filters['template_name'] ?? null, fn ($q, $v) => $q->whereHas('template', fn ($q) => $q->where('name', 'LIKE', '%'.$v.'%')))
            ->when($filters['limit'] ?? null, fn ($q, $v) => $q->limit($v))->get();
        if ($events->isEmpty()) {
            return response()->json(['status' => 204, 'error' => false, 'message' => 'No events found', 'data' => []]);
        }

        return response()->json(['status' => 200, 'error' => false, 'message' => 'Success', 'total' => $events->count(), 'data' => ($filters['template_id'] ?? null) || ($filters['template_name'] ?? null) ? $events : $events->groupBy('template.name')]);
    }

    public function versioned(Request $request)
    {
        $filters = $request->validate(['source' => ['nullable', 'in:'.implode(',', InboundSources::EVENT_SOURCES)], 'status' => ['nullable', 'in:draft,published,cancelled'], 'starts_after' => ['nullable', 'date'], 'starts_before' => ['nullable', 'date'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $events = Event::with('template:id,name')
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['starts_after'] ?? null, fn ($q, $v) => $q->where('starts_at', '>=', $v))
            ->when($filters['starts_before'] ?? null, fn ($q, $v) => $q->where('starts_at', '<=', $v))
            ->orderByDesc('id')->paginate($filters['per_page'] ?? 25)->withQueryString();

        return EventResource::collection($events);
    }

    private function data(Request $request, bool $validateCustom = true, ?int $retainedTemplateId = null): array
    {
        $data = $request->validate(['template_id' => ['nullable', Rule::exists('event_templates', 'id')->whereNull('deleted_at')], 'event_data' => ['present', 'array'], 'title' => ['sometimes', 'nullable', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:100000'], 'location' => ['sometimes', 'nullable', 'string', 'max:5000'], 'status' => ['sometimes', 'in:draft,published,cancelled'], 'starts_at' => ['sometimes', 'nullable', 'date'], 'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'], 'timezone' => ['sometimes', 'nullable', 'timezone'], 'all_day' => ['sometimes', 'boolean'], 'recurrence' => ['nullable', 'array:frequency,interval,weekdays,monthly_mode,count,until'], 'recurrence.frequency' => ['required_with:recurrence', 'in:daily,weekly,monthly,yearly'], 'recurrence.interval' => ['required_with:recurrence', 'integer', 'min:1', 'max:100'], 'recurrence.count' => ['required_with:recurrence', 'integer', 'min:1', 'max:1000'], 'recurrence.until' => ['nullable', 'date_format:Y-m-d'], 'recurrence.weekdays' => ['sometimes', 'array', 'min:1', 'max:7'], 'recurrence.weekdays.*' => ['integer', 'between:1,7', 'distinct'], 'recurrence.monthly_mode' => ['sometimes', 'in:date,weekday']]);
        $template = ! empty($data['template_id']) ? EventTemplate::findOrFail($data['template_id']) : null;
        abort_unless(! $template || $template->source === 'eventnest' || $template->id === $retainedTemplateId, 403, 'This template is managed by its provider.');
        foreach (['starts_at', 'ends_at'] as $key) {
            $value = $request->input($key);
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', $value)) {
                $parsed = CarbonImmutable::parse($value, $request->input('timezone') ?: 'UTC');
                if ($parsed->format(strlen($value) === 16 ? 'Y-m-d\TH:i' : 'Y-m-d\TH:i:s') !== $value) {
                    throw ValidationException::withMessages([$key => 'This local time does not exist in the selected timezone.']);
                }
            }
        }
        $fields = FieldTemplate::whereIn('id', $template?->fields ?? [])->get();
        $data['event_data'] = $this->mirrorCustomFields($data['event_data'], $request->only(array_values(self::CUSTOM_ALIASES)), $fields);
        if ($validateCustom) {
            $this->validateCustomFields($data['event_data'], $fields);
        }

        if ($request->input('status') === 'published' || $request->filled('recurrence')) {
            $request->validate(self::PUBLISH_RULES);
        }
        if ($request->filled('recurrence.until') && $request->filled('starts_at') && substr($request->input('starts_at'), 0, 10) > $request->input('recurrence.until')) {
            throw ValidationException::withMessages(['recurrence.until' => 'The repeat end date must be on or after the first event date.']);
        }
        if ($request->input('recurrence.frequency') === 'weekly' && ! $request->input('recurrence.weekdays')) {
            throw ValidationException::withMessages(['recurrence.weekdays' => 'Choose at least one weekday.']);
        }
        if ($request->boolean('all_day')) {
            $request->validate(['starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at']]);
            if (substr($request->input('starts_at'), 11, 5) !== '00:00' || substr($request->input('ends_at'), 11, 5) !== '00:00') {
                throw ValidationException::withMessages(['all_day' => 'All-day events must start and end at midnight; the end date is exclusive.']);
            }
        }
        $request->validate(['ends_at' => ['nullable', 'date', 'required_with:recurrence', 'after_or_equal:starts_at']]);

        return ['template_id' => $data['template_id'] ?? null, 'event_data' => $data['event_data']];
    }

    private function mirrorCustomFields(array $eventData, array $changes, iterable $fields): array
    {
        // Mirror explicitly supplied canonical fields for legacy template consumers.
        foreach ($fields as $field) {
            $key = self::CUSTOM_ALIASES[$field->field_id] ?? null;
            if ($key && array_key_exists($key, $changes) && (! in_array($key, ['starts_at', 'ends_at']) || filled($changes[$key]))) {
                $eventData[$field->field_id] = $changes[$key];
            }
        }

        return $eventData;
    }

    private function validateCustomFields(array $eventData, iterable $fields): void
    {
        $rules = [];
        $attributes = [];
        foreach ($fields as $field) {
            $type = match ($field->type) {
                'number' => 'integer', 'decimal' => 'numeric', 'boolean', 'checkbox' => 'boolean', 'date', 'datetime' => 'date', 'email' => 'email', 'url', 'image', 'file' => 'url', default => 'string'
            };
            $name = 'event_data.'.$field->field_id;
            $attributes[$name] = $field->label;
            $rules[$name] = [$field->required && ! in_array($field->type, ['boolean', 'checkbox']) ? 'required' : 'nullable', $type];
            if ($field->type === 'select') {
                $rules[$name][] = Rule::in(is_array($field->options) ? $field->options : (json_decode($field->options ?? '[]', true) ?: []));
            }
        }
        Validator::make(['event_data' => $eventData], $rules, [], $attributes)->validate();
    }

    private function canonical(Request $request, array $data): array
    {
        $title = $request->input('title') ?: ($data['title'] ?? $data['name'] ?? null);

        return ['title' => is_string($title) ? mb_substr($title, 0, 255) : null, 'description' => $request->input('description'), 'location' => $request->input('location'), 'status' => $request->input('status', 'draft'), 'timezone' => $request->input('timezone') ?: 'UTC', 'all_day' => $request->boolean('all_day'), 'recurrence' => $request->input('recurrence'),
            'starts_at' => $request->filled('starts_at') ? CarbonImmutable::parse($request->input('starts_at'), $request->input('timezone') ?: 'UTC')->utc() : null,
            'ends_at' => $request->filled('ends_at') ? CarbonImmutable::parse($request->input('ends_at'), $request->input('timezone') ?: 'UTC')->utc() : null];
    }

    public function store(Request $request)
    {
        $api = $request->routeIs('api.v1.events.store');
        if ($api) {
            $this->prepareApiInput($request);
        }
        $data = $this->data($request);
        $event = DB::transaction(function () use ($data, $request) {
            $event = new Event($data);
            $event->forceFill($this->canonical($request, $data['event_data']))->save();

            return $event;
        });

        if ($api) {
            return $this->apiShow($event)->response()->setStatusCode(201)
                ->header('Location', route('api.v1.events.show', $event));
        }

        return to_route('events.index')->with('success', 'Event created successfully.');
    }

    public function update(Request $request, Event $event)
    {
        $api = $request->routeIs('api.v1.events.update');
        if ($api) {
            $this->prepareApiInput($request, replacing: true);
        }
        $event = DB::transaction(function () use ($event, $request, $api) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $data = $this->data($request, retainedTemplateId: $api ? $event->template_id : null);
            $canonical = $this->canonical($request, $data['event_data']);
            $changingSchedule = $event->recurrence != $canonical['recurrence'] || $event->starts_at?->toIso8601String() !== $canonical['starts_at']?->toIso8601String() || $event->timezone !== $canonical['timezone'] || $event->all_day !== $canonical['all_day'];
            if ($changingSchedule && DB::table('event_exceptions')->where('event_id', $event->id)->exists()) {
                throw ValidationException::withMessages(['recurrence' => 'This series has occurrence changes. Keep its original schedule or create a new series.']);
            }
            if ($event->source !== 'eventnest') {
                $overrides = $event->local_overrides ?? [];
                foreach ($canonical + $data as $key => $value) {
                    $before = $event->{$key};
                    if (in_array($key, ['starts_at', 'ends_at'])) {
                        $before = $before?->toIso8601String();
                        $value = $value?->toIso8601String();
                    }
                    if ($before != $value) {
                        $overrides[] = $key;
                        if ($key === 'event_data') {
                            $overrides[] = 'template_id';
                        }
                        if (in_array($key, ['starts_at', 'ends_at', 'timezone', 'all_day', 'recurrence'])) {
                            array_push($overrides, 'starts_at', 'ends_at', 'timezone', 'all_day', 'recurrence');
                        }
                    }
                }
                // Custom answers remain local when the user switches template.
                if ($event->template_id != $data['template_id']) {
                    $overrides[] = 'event_data';
                }
                $event->forceFill(['local_overrides' => array_values(array_unique($overrides))]);
            }
            $event->fill($data)->forceFill($canonical)->save();

            return $event;
        });

        if ($api) {
            return $this->apiShow($event);
        }

        return to_route('events.index')->with('success', 'Event updated successfully.');
    }

    private function prepareApiInput(Request $request, bool $replacing = false): void
    {
        $body = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => [$replacing ? 'required' : 'sometimes', 'in:draft,published,cancelled'],
            'custom_fields' => ['sometimes', 'array'],
        ]);
        $request->merge(['event_data' => $body['custom_fields'] ?? []]);
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return to_route('events.index')->with('success', 'Event removed from EventJunction.');
    }

    private function bulkSelection(Request $request): array
    {
        $selection = $request->validate([
            'workspace_id' => ['required', 'integer'],
            'ids' => ['required', 'list', 'min:1', 'max:'.self::BULK_LIMIT],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);
        if ((int) $selection['workspace_id'] !== $request->user()->currentTeam->id) {
            throw ValidationException::withMessages(['ids' => 'The workspace changed. Select the events again.']);
        }

        return $selection['ids'];
    }

    private function lockSelectedEvents(array $ids, bool $withTemplates = false): Collection
    {
        $query = Event::whereKey($ids)->orderBy('id')->lockForUpdate();
        $events = ($withTemplates ? $query->with('template') : $query)->get();
        if ($events->count() !== count($ids)) {
            throw ValidationException::withMessages(['ids' => 'Some selected events are no longer available. Clear the selection and try again.']);
        }

        return $events;
    }

    public function bulkUpdate(Request $request)
    {
        $ids = $this->bulkSelection($request);
        $changes = $request->validate([
            'status' => ['sometimes', 'required', 'in:draft,published,cancelled'],
            'location' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'description' => ['sometimes', 'nullable', 'string', 'max:100000'],
        ]);
        if (! $changes) {
            throw ValidationException::withMessages(['changes' => 'Choose at least one field to update.']);
        }

        DB::transaction(function () use ($ids, $changes) {
            $events = $this->lockSelectedEvents($ids, withTemplates: true);
            $fields = FieldTemplate::whereKey($events->flatMap(fn (Event $event) => $event->template?->fields ?? [])->unique()->values()->all())->get()->keyBy('id');
            foreach ($events as $event) {
                $templateFields = $fields->only($event->template?->fields ?? []);
                $updates = $changes;
                $eventData = $event->event_data ?? [];
                $updatedData = $this->mirrorCustomFields($eventData, $changes, $templateFields);
                if ($updatedData !== $eventData) {
                    $updates['event_data'] = $updatedData;
                }
                if (($changes['status'] ?? null) === 'published') {
                    try {
                        $this->validateCustomFields($updatedData, $templateFields);
                        Validator::make($event->only(array_keys(self::PUBLISH_RULES)), self::PUBLISH_RULES)->validate();
                    } catch (ValidationException $error) {
                        $title = $event->title ?: "Event #{$event->id}";
                        throw ValidationException::withMessages(['status' => "Cannot publish {$title}. ".$error->validator->errors()->first()]);
                    }
                }
                if ($event->source !== 'eventnest') {
                    $overrides = array_merge($event->local_overrides ?? [], array_keys($updates));
                    if (array_key_exists('event_data', $updates)) {
                        $overrides[] = 'template_id';
                    }
                    $updates['local_overrides'] = array_values(array_unique($overrides));
                }
                $event->forceFill($updates)->save();
            }
        }, 3);

        return back()->with('success', trans_choice(':count event updated.|:count events updated.', count($ids)));
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $this->bulkSelection($request);
        DB::transaction(function () use ($ids) {
            foreach ($this->lockSelectedEvents($ids) as $event) {
                $event->delete();
            }
        }, 3);

        return back()->with('success', trans_choice(':count event deleted.|:count events deleted.', count($ids)));
    }
}
