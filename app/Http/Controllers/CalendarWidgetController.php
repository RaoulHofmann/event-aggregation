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
            'appearance' => $widget?->appearanceSettings() ?? CalendarWidget::APPEARANCE,
            'themes' => CalendarWidget::THEMES,
        ]);
    }

    public function save(Request $request)
    {
        $workspace = $this->ownedWorkspace($request);
        $settings = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'source' => ['nullable', Rule::in(InboundSources::EVENT_SOURCES)],
            'template_id' => ['nullable', 'integer', Rule::exists('event_templates', 'id')->whereNull('deleted_at')],
            'appearance' => ['sometimes', 'required', 'array:theme,brand,compact,show_description,show_credit'],
            'appearance.theme' => ['required_with:appearance', Rule::in(array_keys(CalendarWidget::THEMES))],
            'appearance.brand' => ['nullable', 'string', 'max:70'],
            'appearance.compact' => ['required_with:appearance', 'boolean'],
            'appearance.show_description' => ['required_with:appearance', 'boolean'],
            'appearance.show_credit' => ['required_with:appearance', 'boolean'],
        ]);

        $row = [
            'team_id' => $workspace->id,
            'public_id' => Str::random(40),
            'title' => $settings['title'],
            'source' => $settings['source'] ?? null,
            'template_id' => $settings['template_id'] ?? null,
        ];
        $changes = ['title', 'source', 'template_id', 'updated_at'];
        if (isset($settings['appearance'])) {
            foreach (['compact', 'show_description', 'show_credit'] as $option) {
                $settings['appearance'][$option] = (bool) $settings['appearance'][$option];
            }
            $row['appearance'] = json_encode($settings['appearance'], JSON_THROW_ON_ERROR);
            $changes[] = 'appearance';
        }
        CalendarWidget::upsert([$row], ['team_id'], $changes);

        return to_route('events.widget')->with('success', 'Widget settings saved.');
    }

    public function disable(Request $request)
    {
        $workspace = $this->ownedWorkspace($request);
        CalendarWidget::where('team_id', $workspace->id)->delete();

        return to_route('events.widget')->with('success', 'Widget disabled. The old embed link has been revoked.');
    }

    public function show(Request $request, string $widget, EventOccurrences $occurrences)
    {
        DB::select("SELECT set_config('search_path', 'public', false)");
        try {
            $calendar = CalendarWidget::with('workspace:id,schema_name,suspended_at')->where('public_id', $widget)->firstOrFail();
            $workspace = $calendar->workspace;
            abort_unless($workspace && ! $workspace->suspended_at && $this->isTenantSchema($workspace->schema_name), 404);
            $this->setSchema($workspace->schema_name);
            $appearance = $calendar->appearanceSettings();

            $from = Date::now('UTC');
            try {
                $events = $occurrences->window($from, $from->addDays(60), [
                    'status' => 'published', 'source' => $calendar->source, 'template' => $calendar->template_id,
                ], $workspace->id);
            } catch (HttpExceptionInterface $error) {
                if ($error->getStatusCode() !== 422) {
                    throw $error;
                }

                return $this->agenda($request, $calendar->title, [], $appearance, 'The calendar is temporarily unavailable.', 503);
            }

            return $this->agenda($request, $calendar->title, array_slice($events, 0, 12), $appearance);
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }
    }

    public function example(Request $request)
    {
        $start = Date::now('Australia/Perth')->addDays(3)->setTime(10, 0);
        $theme = $request->query()['theme'] ?? null;
        $theme = is_string($theme) && isset(CalendarWidget::THEMES[$theme]) ? $theme : 'paper';
        $appearance = [
            'theme' => $theme,
            'brand' => match ($theme) {
                'sage' => 'Fremantle Community Garden',
                'ink' => 'Perth Weekend Guide',
                'lilac' => 'Community Calendar',
                default => null,
            },
            'show_description' => true,
        ] + CalendarWidget::APPEARANCE;

        $events = [
            ['id' => 'garden-workshop', 'title' => 'Community garden workshop', 'location' => 'Fremantle Community Garden', 'starts_at' => $start->toIso8601String(), 'ends_at' => $start->addHours(2)->toIso8601String(), 'description' => 'Plant seasonal seedlings and learn how to keep your garden growing through the warmer months. Gloves and tools are provided.'],
            ['id' => 'open-day', 'title' => 'Neighbourhood open day', 'location' => 'Maylands Community Hall', 'starts_at' => $start->addDays(3)->startOfDay()->toIso8601String(), 'all_day' => true, 'description' => 'Meet groups from around Maylands, see what they run throughout the year, and find something to join.'],
            ['id' => 'repair-cafe', 'title' => 'Repair café', 'location' => 'Perth Tool Library', 'starts_at' => $start->addDays(6)->setTime(14, 0)->toIso8601String(), 'description' => 'Bring a small household item and work with a volunteer to see if it can be repaired.'],
        ];
        $events = array_map(fn ($event) => $event + ['timezone' => $start->timezoneName, 'all_day' => false], $events);

        return $this->agenda($request, 'Upcoming events', $events, $appearance, example: true);
    }

    private function ownedWorkspace(Request $request): Team
    {
        $workspace = $request->user()->currentTeam;
        abort_unless($workspace && $request->user()->ownsTeam($workspace), 403);

        return $workspace;
    }

    private function agenda(Request $request, string $title, array $events, array $appearance, ?string $message = null, int $status = 200, bool $example = false)
    {
        $selected = $request->query()['event'] ?? null;
        $path = $request->getBaseUrl().$request->getPathInfo();
        $query = $example && $appearance['theme'] !== 'paper' ? ['theme' => $appearance['theme']] : [];
        $link = fn (array $parameters) => $path.($parameters ? '?'.http_build_query($parameters, encoding_type: PHP_QUERY_RFC3986) : '');
        $back = $link($query);
        $items = array_map(function (array $event) use ($link, $query) {
            $start = Date::parse($event['starts_at'])->setTimezone($event['timezone']);

            return [
                'title' => $event['title'],
                'url' => $link($query + ['event' => $event['id']]),
                'location' => $event['location'],
                'day' => $start->format('j'),
                'month' => $start->format('M'),
                'date' => $start->format('l j F Y'),
                'starts' => $start->toIso8601String(),
                'time' => $event['all_day'] ? 'All day' : $start->format('g:ia T'),
            ];
        }, $events);
        $detail = null;
        if ($selected !== null && $status === 200) {
            $index = array_search($selected, array_column($events, 'id'), true);
            if ($index === false) {
                return $this->agendaResponse(compact('title', 'appearance', 'example', 'back') + [
                    'items' => [], 'detail' => null, 'message' => 'This event is no longer listed.', 'showBack' => true,
                ], 404);
            }
            $event = $events[$index];
            $detail = $items[$index] + [
                'description' => $appearance['show_description'] ? $event['description'] ?? null : null,
                'cover' => empty($event['image_url']) ? null : parse_url($event['image_url'], PHP_URL_PATH),
            ];
            $this->setDetailTime($detail, $event);
        }

        return $this->agendaResponse(compact('title', 'items', 'message', 'example', 'appearance', 'detail', 'back') + ['showBack' => $selected !== null], $status);
    }

    private function setDetailTime(array &$detail, array $event): void
    {
        if (empty($event['ends_at']) || $event['starts_at'] === $event['ends_at']) {
            return;
        }
        $start = Date::parse($event['starts_at'])->setTimezone($event['timezone']);
        $end = Date::parse($event['ends_at'])->setTimezone($event['timezone']);
        if ($event['all_day']) {
            $last = $end->subDay();
            if ($last->isAfter($start)) {
                $detail['date'] .= ' – '.$last->format('l j F Y');
            }
        } else {
            $detail['time'] .= ' – '.$end->format($end->isSameDay($start) ? 'g:ia T' : 'l j F Y, g:ia T');
        }
    }

    private function agendaResponse(array $data, int $status)
    {
        return response()->view('widgets.upcoming', $data, $status, [
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'Content-Security-Policy' => "default-src 'none'; style-src 'self'; img-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors *",
            'X-EventJunction-Embed' => '1',
        ]);
    }
}
