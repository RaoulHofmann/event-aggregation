<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventField;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        return Inertia::render('Events/Index', [
            'events' => Event::latest()->get(),
            'fields' => EventField::latest()->get()
        ]);
    }

    // Show the form for creating a new event
    public function create()
    {
        return Inertia::render('Events/Create', [
            'fields' => EventField::latest()->get()  // Pass fields to the creation page if needed
        ]);
    }

    // Store a newly created event
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'venue_name' => 'nullable|string',
            'city' => 'nullable|string',
            'type' => 'nullable|string',
            'status' => 'nullable|string|in:draft,published,cancelled',
            'capacity' => 'nullable|integer',
            'registration_required' => 'nullable|boolean',
            'registration_deadline' => 'nullable|date',
            'price' => 'nullable|numeric',
            'organizer_name' => 'nullable|string',
            'organizer_email' => 'nullable|email',
            'custom_fields' => 'nullable|array',
            'featured_image' => 'nullable|string'
        ]);

        // Create a new event with validated data
        $event = Event::create($validated);

        // Redirect back with success message
        return redirect()->refresh()->with('success', 'Event created successfully.');
    }


    // Show the form for editing the specified event
    public function edit(Event $event)
    {
        // Pass the event and the available fields to the edit page
        return Inertia::render('Events/Edit', [
            'event' => $event,
            'fields' => EventField::latest()->get()  // Pass fields if needed for the event editing form
        ]);
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
