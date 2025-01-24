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
            'event_templates' => EventTemplate::latest()->get(),
        ]);
    }

    public function create()
    {
        return FieldTemplate::latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:event_templates,name',
            'description' => 'nullable|string',
            'field_configurations' => 'required|array',
            'field_configurations.*' => 'exists:field_templates,id',
        ]);

        EventTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'field_configurations' => $validated['field_configurations'],
        ]);

        return redirect()->route('event-templates.index')
            ->with('success', 'Event template created successfully.');
    }

    public function edit(EventTemplate $eventTemplate)
    {
        $available_fields = FieldTemplate::latest()->get();
        return Inertia::render('EventTemplates/Edit', [
            'event_template' => $eventTemplate,
            'available_fields' => $available_fields,
        ]);
    }

    public function update(Request $request, EventTemplate $eventTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:event_templates,name,' . $eventTemplate->id,
            'description' => 'nullable|string',
            'field_configurations' => 'required|array',
            'field_configurations.*' => 'exists:field_templates,id',
        ]);

        $eventTemplate->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'field_configurations' => $validated['field_configurations'],
        ]);

        return redirect()->route('event-templates.index')
            ->with('success', 'Event template updated successfully.');
    }

    public function destroy(EventTemplate $eventTemplate)
    {
        $eventTemplate->delete();

        return redirect()->route('event-templates.index')
            ->with('success', 'Event template deleted successfully.');
    }
}
