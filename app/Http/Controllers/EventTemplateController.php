<?php

namespace App\Http\Controllers;

use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('EventTemplates/Index', [
            'event-templates' => EventTemplate::orderBy('name')->get()->values(),
        ]);
    }

    public function get()
    {
        return EventTemplate::orderBy('name')->get()->values();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:event_templates,name',
            'description' => 'nullable|string',
            'fields' => 'required|array',
            'fields.*' => 'exists:field_templates,id',
            'layout' => 'required|array',
        ]);

        EventTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'fields' => $validated['fields'],
            'layout' => $validated['layout'],
        ]);

        session()->flash('success', 'Event template created successfully.');
    }

    public function update(Request $request, EventTemplate $eventTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:event_templates,name,' . $eventTemplate->id,
            'description' => 'nullable|string',
            'fields' => 'required|array',
            'fields.*' => 'exists:field_templates,id',
        ]);

        $eventTemplate->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'fields' => $validated['fields'],
        ]);

        session()->flash('success', 'Event template updated successfully.');
    }

    public function destroy(EventTemplate $eventTemplate)
    {
        $eventTemplate->delete();

        return redirect()->route('event-templates.index')
            ->with('success', 'Event template deleted successfully.');
    }
}
