<?php

namespace App\Integrations\Inbound;

use Illuminate\Support\Facades\Http;
use Throwable;

class EventbriteEvents
{
    public function read(string $organization, string $token): array
    {
        $query = ['expand' => 'venue', 'page_size' => 50];
        $seen = [];
        $events = [];
        for ($page = 1; $page <= 40; $page++) {
            try {
                $response = Http::withToken($token)->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(12)
                    ->get('https://www.eventbriteapi.com/v3/organizations/'.rawurlencode($organization).'/events/', $query);
            } catch (Throwable) {
                throw new ImportException('Eventbrite could not be reached.');
            }
            if ($response->status() === 401) {
                throw new ReconnectRequired('Eventbrite rejected the access token. Update the connection to reconnect.');
            }
            $items = $response->json('events');
            $paging = $response->json('pagination');
            if (! $response->successful() || ! is_array($items) || ! is_array($paging) || ! is_bool($paging['has_more_items'] ?? null)) {
                throw new ImportException('Eventbrite could not list events. Check the organization ID and token access.');
            }
            foreach ($items as $event) {
                $events[] = ImportedEvent::make((string) ($event['id'] ?? ''), [
                    'title' => $event['name']['text'] ?? null,
                    'description' => $event['description']['text'] ?? null,
                    'location' => $event['venue']['address']['localized_address_display'] ?? $event['venue']['name'] ?? null,
                    'starts_at' => $event['start']['utc'] ?? null,
                    'ends_at' => $event['end']['utc'] ?? null,
                    'timezone' => $event['start']['timezone'] ?? 'UTC',
                    'url' => $event['url'] ?? null,
                    'cancelled' => in_array($event['status'] ?? null, ['canceled', 'cancelled'], true),
                ], [
                    'venue_name' => $event['venue']['name'] ?? null,
                    'capacity' => $event['capacity'] ?? null,
                ]);
            }
            if (count($events) > 2000) {
                break;
            }
            if ($paging['has_more_items'] === false) {
                return $events;
            }
            $next = $paging['continuation'] ?? null;
            if ($next) {
                if (! is_string($next) || isset($seen[$next]) || strlen($next) > 2048) {
                    break;
                }
                $seen[$next] = true;
                $query['continuation'] = $next;
            } else {
                $query['page'] = $page + 1;
            }
        }

        throw new ImportException('Eventbrite pagination exceeded the current import limit or returned a repeated cursor.');
    }
}
