<?php

namespace App\Integrations\Inbound;

use Carbon\CarbonImmutable;
use Throwable;

class WordPressEvents
{
    public function __construct(private PublicFeedClient $http) {}

    public function read(string $site, string $fallback): array
    {
        $events = [];
        for ($page = 1; $page <= 40; $page++) {
            $body = $this->http->get(rtrim($site, '/').'/wp-json/tribe/events/v1/events', [
                'page' => $page,
                'per_page' => 50,
                'start_date' => now()->subDays(90)->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
            ]);
            $payload = json_decode($body, true);
            if (! is_array($payload['events'] ?? null) || ! is_numeric($payload['total_pages'] ?? null)) {
                throw new ImportException('This site did not return a public The Events Calendar API response.');
            }

            foreach ($payload['events'] as $event) {
                $allDay = (bool) ($event['all_day'] ?? false);
                $zone = $event['timezone'] ?? $fallback;
                try {
                    new \DateTimeZone($zone);
                } catch (Throwable) {
                    $zone = $fallback;
                }
                $start = $allDay ? substr($event['start_date'] ?? '', 0, 10) : ($event['start_date'] ?? null);
                $end = $allDay ? substr($event['end_date'] ?? '', 0, 10) : ($event['end_date'] ?? null);
                if (! $allDay) {
                    $start = ! empty($event['utc_start_date']) ? $event['utc_start_date'].'Z' : $start;
                    $end = ! empty($event['utc_end_date']) ? $event['utc_end_date'].'Z' : $end;
                }
                if ($allDay && $end !== '') {
                    $end = CarbonImmutable::parse($end, $zone)->addDay()->toDateString();
                }
                $events[] = ImportedEvent::make((string) ($event['id'] ?? ''), [
                    'title' => $event['title'] ?? null,
                    'description' => $event['description'] ?? null,
                    'location' => $event['venue']['venue'] ?? $event['venue']['address'] ?? null,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'timezone' => $zone,
                    'all_day' => $allDay,
                    'url' => $event['url'] ?? null,
                ], [
                    'organiser' => $this->names((array) ($event['organizer'] ?? []), 'organizer'),
                    'categories' => $this->names((array) ($event['categories'] ?? []), 'name'),
                ]);
            }

            if (count($events) > 2000) {
                break;
            }
            if ($page >= (int) $payload['total_pages']) {
                return $events;
            }
        }

        throw new ImportException('The WordPress event list exceeds the current import limit.');
    }

    private function names(array $items, string $key): string
    {
        $names = array_filter(array_column($items, $key), 'is_string');

        return implode(', ', $names);
    }
}
