<?php

namespace App\Http\Controllers;

use App\Integrations\Inbound\InboundSources;
use App\Models\CalendarWidget;
use App\Models\EventTemplate;
use App\Models\Team;
use App\Services\EventOccurrences;
use App\Support\PublicSite;
use App\Traits\SchemaManagement;
use Carbon\CarbonImmutable as Date;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CalendarWidgetController extends Controller
{
    use SchemaManagement;

    public function index(Request $request)
    {
        $workspace = $this->ownedWorkspace($request);
        $widget = CalendarWidget::where('team_id', $workspace->id)->first();
        $path = $widget ? route('widgets.upcoming.show', $widget->public_id, absolute: false) : null;

        return Inertia::render('Events/Widget', [
            'widget' => $widget?->only('title', 'source', 'template_id'),
            'previewPath' => $path,
            'embedUrl' => $path ? PublicSite::url($path) : null,
            'templates' => EventTemplate::orderBy('name')->get(['id', 'name']),
            'sources' => InboundSources::EVENT_SOURCES,
        ]);
    }

    public function save(Request $request)
    {
        $workspace = $this->ownedWorkspace($request);
        $settings = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'source' => ['nullable', Rule::in(InboundSources::EVENT_SOURCES)],
            'template_id' => ['nullable', 'integer', Rule::exists('event_templates', 'id')->whereNull('deleted_at')],
        ]);

        CalendarWidget::upsert([[
            'team_id' => $workspace->id,
            'public_id' => Str::random(40),
            'title' => $settings['title'],
            'source' => $settings['source'] ?? null,
            'template_id' => $settings['template_id'] ?? null,
        ]], ['team_id'], ['title', 'source', 'template_id', 'updated_at']);

        return to_route('events.widget')->with('success', 'Widget settings saved.');
    }

    public function disable(Request $request)
    {
        $workspace = $this->ownedWorkspace($request);
        CalendarWidget::where('team_id', $workspace->id)->delete();

        return to_route('events.widget')->with('success', 'Widget disabled. The old embed link has been revoked.');
    }

    public function show(string $widget, EventOccurrences $occurrences)
    {
        DB::select("SELECT set_config('search_path', 'public', false)");
        try {
            $calendar = CalendarWidget::with('workspace:id,schema_name,suspended_at')->where('public_id', $widget)->firstOrFail();
            $workspace = $calendar->workspace;
            abort_unless($workspace && ! $workspace->suspended_at && $this->isTenantSchema($workspace->schema_name), 404);
            $this->setSchema($workspace->schema_name);

            $from = Date::now('UTC');
            try {
                $events = $occurrences->window($from, $from->addDays(60), [
                    'status' => 'published', 'source' => $calendar->source, 'template' => $calendar->template_id,
                ]);
            } catch (HttpExceptionInterface $error) {
                if ($error->getStatusCode() !== 422) {
                    throw $error;
                }

                return $this->agenda($calendar->title, [], 'The calendar is temporarily unavailable.', 503);
            }

            return $this->agenda($calendar->title, array_slice($events, 0, 12));
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }
    }

    public function example()
    {
        $start = Date::now('Australia/Perth')->addDays(3)->setTime(10, 0);

        return $this->agenda('Upcoming events', [
            ['title' => 'Community garden workshop', 'location' => 'Fremantle Community Garden', 'starts_at' => $start->toIso8601String(), 'timezone' => $start->timezoneName, 'all_day' => false],
            ['title' => 'Neighbourhood open day', 'location' => 'Maylands Community Hall', 'starts_at' => $start->addDays(3)->toIso8601String(), 'timezone' => $start->timezoneName, 'all_day' => true],
            ['title' => 'Repair café', 'location' => 'Perth Tool Library', 'starts_at' => $start->addDays(6)->setTime(14, 0)->toIso8601String(), 'timezone' => $start->timezoneName, 'all_day' => false],
        ], example: true);
    }

    private function ownedWorkspace(Request $request): Team
    {
        $workspace = $request->user()->currentTeam;
        abort_unless($workspace && $request->user()->ownsTeam($workspace), 403);

        return $workspace;
    }

    private function agenda(string $title, array $events, ?string $message = null, int $status = 200, bool $example = false)
    {
        $items = array_map(function (array $event) {
            $start = Date::parse($event['starts_at'])->setTimezone($event['timezone']);

            return [
                'title' => $event['title'],
                'location' => $event['location'],
                'day' => $start->format('j'),
                'month' => $start->format('M'),
                'date' => $start->format('l j F Y'),
                'starts' => $start->toIso8601String(),
                'time' => $event['all_day'] ? 'All day' : $start->format('g:ia T'),
            ];
        }, $events);

        return response()->view('widgets.upcoming', compact('title', 'items', 'message', 'example'), $status, [
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'Content-Security-Policy' => "default-src 'none'; style-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors *",
            'X-EventJunction-Embed' => '1',
        ]);
    }
}
