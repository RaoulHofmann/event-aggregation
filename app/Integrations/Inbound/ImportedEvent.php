<?php

namespace App\Integrations\Inbound;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Throwable;

final readonly class ImportedEvent
{
    public function __construct(public string $key, public array $fields, public ?string $url = null, public bool $cancelled = false, public array $metadata = []) {}

    public static function make(string $key, array $data, array $metadata = []): self
    {
        $key = trim($key);
        if ($key === '' || strlen($key) > 2048) {
            throw new ImportException('An event is missing a usable source identifier.');
        }
        if ($data['cancelled'] ?? false) {
            return new self(hash('sha256', $key), [], cancelled: true);
        }

        $timezone = $data['timezone'] ?? 'UTC';
        try {
            new DateTimeZone($timezone);
        } catch (Throwable) {
            throw new ImportException('An event has an unsupported timezone.');
        }
        $title = self::text($data['title'] ?? null, 255);
        if ($title === null) {
            throw new ImportException('An event is missing its title.');
        }
        $allDay = (bool) ($data['all_day'] ?? false);
        $start = self::date($data['starts_at'] ?? null, $timezone, $allDay);
        $end = self::date($data['ends_at'] ?? null, $timezone, $allDay);
        if ((! $start && ($end || $allDay)) || ($end && $end->lt($start))) {
            throw new ImportException('An event has an invalid date range.');
        }
        if ($start && $allDay && (! $end || $end->eq($start))) {
            $end = $start->addDay();
        }
        $url = $data['url'] ?? null;
        $parts = is_string($url) ? parse_url($url) : false;
        $url = is_array($parts) && ($parts['scheme'] ?? null) === 'https' && ! isset($parts['user']) && filter_var($url, FILTER_VALIDATE_URL) && strlen($url) < 2048 ? $url : null;

        return new self(hash('sha256', $key), [
            'title' => $title,
            'description' => self::text($data['description'] ?? null, 30000),
            'location' => self::text($data['location'] ?? null, 2048),
            'starts_at' => $start?->utc()->toIso8601String(),
            'ends_at' => $end?->utc()->toIso8601String(),
            'timezone' => $timezone,
            'all_day' => $allDay,
            'recurrence' => null,
            'status' => 'draft',
        ], $url, metadata: array_map(fn ($value) => is_int($value) ? $value : self::text($value, 2048), $metadata));
    }

    private static function date(mixed $value, string $zone, bool $allDay): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                $date = CarbonImmutable::instance($value)->setTimezone($zone);
            } elseif (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2}(?:\.\d{1,9})?)?(?:Z|[+-]\d{2}:\d{2})?)?$/D', $value)
                && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))) {
                $date = CarbonImmutable::parse($value, $zone)->setTimezone($zone);
            } else {
                throw new ImportException;
            }

            return $allDay ? $date->startOfDay() : $date;
        } catch (Throwable) {
            throw new ImportException('Dates must use ISO format, for example 2026-11-18 or 2026-11-18T10:00:00+08:00.');
        }
    }

    private static function text(mixed $value, int $limit): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim(str_replace("\0", '', strip_tags(html_entity_decode(mb_scrub($value, 'UTF-8'), ENT_QUOTES, 'UTF-8'))));

        return $value === '' ? null : mb_substr($value, 0, $limit, 'UTF-8');
    }
}
