<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventField;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        return Inertia::render('Events/Index', [
            'events' => Event::latest()->get(),
            'event-templates' => EventTemplate::get()->sortBy('name')->values(),
            'field-templates' => FieldTemplate::get()->sortBy('label')->values(),
        ]);
    }

    public function data()
    {
        return [
            'templates' => EventTemplate::all(),
            'fields' => FieldTemplate::latest()->get(),
        ];
    }

    public function get()
    {
        return Event::latest()->get();
    }

    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'template_id' => 'required|exists:event_templates,id',
            'event_data' => 'required|array',
        ]);

        // Create a new event from the selected template
        $event = Event::create($validated);

        return redirect()->route('events.index')->with('success', 'Event created successfully.');
    }

    // Update the specified event in storage
    public function update(Request $request, Event $event)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        // Update the event
        $event->update($validated);

        // Redirect back with a success message
        return redirect()->route('events')->with('success', 'Event updated successfully.');
    }

    // Remove the specified event from storage
    public function destroy(Event $event)
    {
        // Delete the event
        $event->delete();

        // Redirect back with a success message
        return redirect()->route('events')->with('success', 'Event deleted successfully.');
    }
}
