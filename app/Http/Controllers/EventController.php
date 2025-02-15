<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventField;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\JsonResponse;

class EventController extends Controller
{
    public function index()
    {
        return Inertia::render('Events/Index', [
            'events' => Event::latest()->get(),
            'event-templates' => EventTemplate::orderBy('name')->get()->values(),
            'field-templates' => FieldTemplate::orderBy('label')->get()->values(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/events",
     *     summary="Get a list of events",
     *     description="Retrieves a list of events, optionally filtered by template ID or template name, and grouped by template name if no filters are applied.",
     *     operationId="getEvents",
     *     tags={"Events"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="template_id",
     *         in="query",
     *         description="Filter events by template ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="template_name",
     *         in="query",
     *         description="Filter events by template name (partial match)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit the number of events returned",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="integer",
     *                 example=200
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Success"
     *             ),
     *             @OA\Property(
     *                 property="total",
     *                 type="integer",
     *                 example=10
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\AdditionalProperties(
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Event")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="No events found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="integer",
     *                 example=204
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No events found"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items()
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="integer",
     *                 example=500
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Internal server error"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items()
     *             )
     *         )
     *     )
     * )
     */
    public function get(): JsonResponse
    {
        try {
            $events = Event::latest()
                ->with('template:id,name')
                ->when(request('template_id'), function($query, $templateId) {
                    return $query->where('template_id', $templateId);
                })
                ->when(request('template_name'), function($query, $templateName) {
                    return $query->whereHas('eventTemplate', function($q) use ($templateName) {
                        $q->where('name', 'LIKE', "%{$templateName}%");
                    });
                })
                ->latest()
                ->when(request('limit'), function($query, $limit) {
                    return $query->limit($limit);
                })
                ->get();

            if ($events->isEmpty()) {
                return response()->json([
                    'status' => 204,
                    'error' => false,
                    'message' => 'No events found',
                    'data' => []
                ]);
            }

            $data = request('template_id') || request('template_name')
                ? $events
                : $events->groupBy('template.name');

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Success',
                'total' => $events->count(),
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Event fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'template_id' => 'required|exists:event_templates,id',
            'event_data' => 'required|array',
        ]);

        Event::create($validated);

        return redirect()->route('events.index')->with('success', 'Event created successfully.');
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:event_templates,id',
            'event_data' => 'required|array',
        ]);

        // Update the event
        $event->update($validated);

        return redirect()->route('events.index')->with('success', 'Event updated successfully.');
    }

    // Remove the specified event from storage
    public function destroy(Event $event)
    {
        // Delete the event
        $event->delete();

        // Redirect back with a success message
        return redirect()->route('events.index')->with('success', 'Event deleted successfully.');
    }
}
