<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        return $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('event_templates')->ignore($template?->id)], 'description' => ['nullable', 'string'], 'fields' => ['required', 'array', 'min:1'], 'fields.*' => ['integer', 'exists:field_templates,id'], 'layout' => ['required', 'array'], 'layout.*' => ['array']]);
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
        }$eventTemplate->delete();

        return back()->with('success', 'Template removed.');
    }
}
