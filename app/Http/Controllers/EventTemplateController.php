<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\ProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EventTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('EventTemplates/Index', ['eventTemplates' => EventTemplate::orderBy('name')->get()]);
    }

    public function get()
    {
        return EventTemplate::orderBy('name')->get();
    }

    private function data(Request $request, ?EventTemplate $template = null): array
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('event_templates')->ignore($template?->id)], 'description' => ['nullable', 'string'], 'fields' => ['present', 'array'], 'fields.*' => ['integer', 'distinct', Rule::exists('field_templates', 'id')->whereNull('deleted_at')], 'layout' => ['required', 'array'], 'layout.*' => ['array'], 'layout.*.*' => ['integer', Rule::in($request->input('fields', []))]]);
        $layoutFields = collect($data['layout'])->flatten()->all();
        if (count($layoutFields) !== count(array_unique($layoutFields)) || array_diff($data['fields'], $layoutFields)) {
            throw ValidationException::withMessages(['layout' => 'Place each selected custom field in exactly one column.']);
        }

        return $data;
    }

    public function store(Request $request)
    {
        EventTemplate::create($this->data($request));

        return to_route('event-templates.index')->with('success', 'Template created.');
    }

    public function update(Request $request, EventTemplate $eventTemplate)
    {
        abort_unless($eventTemplate->source === 'eventnest', 403);
        $eventTemplate->update($this->data($request, $eventTemplate));

        return to_route('event-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(EventTemplate $eventTemplate)
    {
        abort_unless($eventTemplate->source === 'eventnest', 403);
        if (Event::where('template_id', $eventTemplate->id)->exists()) {
            return back()->withErrors(['delete' => 'Remove the events using this template first.']);
        }
        if (ProviderConnection::where('template_id', $eventTemplate->id)->exists()) {
            return back()->withErrors(['delete' => 'Change the import template on the connection first.']);
        }
        $eventTemplate->delete();

        return back()->with('success', 'Template removed.');
    }
}
