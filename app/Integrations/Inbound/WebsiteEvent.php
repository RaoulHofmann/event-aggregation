<?php

namespace App\Integrations\Inbound;

use Dom\HTMLDocument;

class WebsiteEvent
{
    public function __construct(private PublicFeedClient $client) {}

    public function read(string $url, string $timezone): ImportedEvent
    {
        $host = $this->client->hostname($url);
        $html = $this->client->get($url);
        if (trim($html) === '') {
            throw new ImportException('The source returned an empty page.');
        }
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $events = [];
        foreach ($document->getElementsByTagName('script') as $script) {
            if (strtolower(trim($script->getAttribute('type') ?? '')) !== 'application/ld+json') {
                continue;
            }
            foreach ($this->events(json_decode($script->textContent, true, 32)) as $candidate) {
                $events[] = $candidate;
                if (count($events) > 1) {
                    throw new ImportException('This page lists multiple events. Use the link to an individual event.');
                }
            }
        }
        $event = $events[0] ?? throw new ImportException('This page has no supported event details. Try the link to a different event.');
        $start = $event['startDate'] ?? null;
        $end = $event['endDate'] ?? null;
        if (! is_string($start) || $start === '') {
            throw new ImportException('This listing is missing a start date.');
        }
        if ($end !== null && ! is_string($end)) {
            throw new ImportException('This listing has an unreadable end date.');
        }
        if (in_array($this->schemaType($event['eventStatus'] ?? null), ['EventCancelled', 'EventPostponed'], true)) {
            throw new ImportException('This listing is marked as cancelled or postponed.');
        }
        $location = $event['location'] ?? null;
        if (is_array($location)) {
            $address = $location['address'] ?? null;
            if (is_array($address)) {
                $address = implode(', ', array_filter([
                    $address['streetAddress'] ?? null, $address['addressLocality'] ?? null, $address['addressRegion'] ?? null,
                ], 'is_string'));
            }
            $location = $location['name'] ?? $address;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        return ImportedEvent::make($host.$path.($query === null ? '' : '?'.$query), [
            'title' => $event['name'] ?? null,
            'starts_at' => $start,
            'ends_at' => $end,
            'all_day' => preg_match('/^\d{4}-\d{2}-\d{2}$/D', $start) === 1,
            'timezone' => $timezone,
            'location' => $location,
            'url' => $url,
        ]);
    }

    private function events(mixed $node): iterable
    {
        if (! is_array($node)) {
            return;
        }
        if (array_is_list($node)) {
            foreach ($node as $child) {
                yield from $this->events($child);
            }

            return;
        }
        foreach ((array) ($node['@type'] ?? []) as $type) {
            if (preg_match('/^(?:Event|[A-Z][a-zA-Z]+Event)$/D', $this->schemaType($type))) {
                yield $node;

                return;
            }
        }
        yield from $this->events($node['@graph'] ?? null);
        yield from $this->events($node['mainEntity'] ?? null);
    }

    private function schemaType(mixed $type): string
    {
        return is_string($type) ? preg_replace('~^https?://schema\.org/~', '', $type) : '';
    }
}
