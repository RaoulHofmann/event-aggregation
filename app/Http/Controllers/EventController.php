<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        return Inertia::render('Events/Index', ['events' => Event::latest()->get(), 'eventTemplates' => EventTemplate::orderBy('name')->get(), 'fieldTemplates' => FieldTemplate::orderBy('label')->get(), 'openCreate' => request()->boolean('create')]);
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
        $filters = $request->validate(['source' => ['nullable', 'in:eventnest,humanitix'], 'status' => ['nullable', 'in:draft,published,cancelled'], 'starts_after' => ['nullable', 'date'], 'starts_before' => ['nullable', 'date'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $events = Event::with('template:id,name')
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['starts_after'] ?? null, fn ($q, $v) => $q->where('starts_at', '>=', $v))
            ->when($filters['starts_before'] ?? null, fn ($q, $v) => $q->where('starts_at', '<=', $v))
            ->orderByDesc('id')->paginate($filters['per_page'] ?? 25)->withQueryString();

        return EventResource::collection($events);
    }

    private function data(Request $request): array
    {
        $data = $request->validate(['template_id' => ['required', 'exists:event_templates,id'], 'event_data' => ['required', 'array'], 'title' => ['sometimes', 'nullable', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:100000'], 'location' => ['sometimes', 'nullable', 'string', 'max:5000'], 'status' => ['sometimes', 'in:draft,published,cancelled'], 'starts_at' => ['sometimes', 'nullable', 'date'], 'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'], 'timezone' => ['sometimes', 'nullable', 'timezone']]);
        $template = EventTemplate::findOrFail($data['template_id']);
        abort_unless($template->source === 'eventnest', 403, 'This template is managed by its provider.');
        $rules = [];
        foreach (FieldTemplate::whereIn('id', $template->fields ?? [])->get() as $field) {
            $type = match ($field->type) {
                'number' => 'integer','decimal' => 'numeric','boolean' => 'boolean','date','datetime' => 'date','email' => 'email','url','image' => 'url',default => 'string'
            };
            $rules['event_data.'.$field->field_id] = [$field->required && $field->type !== 'boolean' ? 'required' : 'nullable', $type];
            if ($field->type === 'select') {
                $rules['event_data.'.$field->field_id][] = Rule::in(is_array($field->options) ? $field->options : (json_decode($field->options ?? '[]', true) ?: []));
            }
        }
        $request->validate($rules);

        return ['template_id' => $data['template_id'], 'event_data' => $data['event_data']];
    }

    private function canonical(Request $request, array $data): array
    {
        $title = $request->input('title') ?: ($data['title'] ?? $data['name'] ?? null);

        return ['title' => is_string($title) ? mb_substr($title, 0, 255) : null, 'description' => $request->input('description'), 'location' => $request->input('location'), 'status' => $request->input('status', 'draft'), 'timezone' => $request->input('timezone'),
            'starts_at' => $request->filled('starts_at') ? CarbonImmutable::parse($request->input('starts_at'), $request->input('timezone') ?: 'UTC')->utc() : null,
            'ends_at' => $request->filled('ends_at') ? CarbonImmutable::parse($request->input('ends_at'), $request->input('timezone') ?: 'UTC')->utc() : null];
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        DB::transaction(fn () => Event::create($data)->forceFill($this->canonical($request, $data['event_data']))->save());

        return to_route('events.index')->with('success', 'Event created successfully.');
    }

    public function update(Request $request, Event $event)
    {
        abort_unless($event->source === 'eventnest', 403, 'Edit this event at its source provider.');
        $data = $this->data($request);
        $event->fill($data)->forceFill($this->canonical($request, $data['event_data']))->save();

        return to_route('events.index')->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return to_route('events.index')->with('success','Event removed from EventNest.');
    }
}
