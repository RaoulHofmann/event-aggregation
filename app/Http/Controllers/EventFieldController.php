<?php

namespace App\Http\Controllers;

use App\Models\EventField;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventFieldController extends Controller
{
    // Fetch and display all event fields (for use with Inertia)
    public function index()
    {
        // Fetch all event fields
        $fields = EventField::latest()->get();

        // Return the event fields to the Inertia view
        return Inertia::render('EventFields/Index', [
            'fields' => $fields,
        ]);
    }

    public function store(Request $request)
    {
        error_log(json_encode($request->json()->all()));

        // Validate incoming request data for a single field
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,date,select',
            'required' => 'boolean',
            'options' => 'array', // Optional for 'select' type
            'options.*' => 'string|max:255', // Validate each option if present
            'event_id' => 'nullable|integer|exists:events,id', // Ensure event_id is valid if provided
        ]);

        // Create the field
        EventField::create([
            'event_id' => $validated['event_id'] ?? 1, // Default to 1 if no event_id is provided
            'name' => $validated['name'],
            'type' => $validated['type'],
            'required' => $validated['required'] ?? false,
            'options' => join(',',$validated['options'] ?? []), // Store options as an array or JSON if needed
        ]);

        return redirect()->back()->with('success', 'Field added successfully.');
    }
}
