<?php

namespace App\Integrations;

use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\ProviderConnection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class SyncGoogleCalendar
{
    public function __construct(private GoogleCalendarClient $client) {}

    public function sync(ProviderConnection $connection): int
    {
        $calendars = $connection->settings['calendars'] ?? [];
        if (! $calendars) {
            throw new GoogleCalendarException('Choose at least one Google calendar before importing.');
        }
        $from = CarbonImmutable::now('UTC')->subDays(90)->startOfDay();
        $to = CarbonImmutable::now('UTC')->addYear()->endOfDay();
        $count = 0;
        $available = collect($this->client->calendars($connection))->keyBy('id');
        foreach ($calendars as $calendar) {
            // Validate current access before treating an individual event's 404 as removal.
            if (! $available->has($calendar['id'])) {
                throw new GoogleCalendarException('A selected Google calendar is no longer readable. Update calendar selection.');
            }
            $calendar = $available[$calendar['id']];
            $seen = [];
            foreach ($this->client->events($connection, $calendar['id'], $from->toRfc3339String(), $to->toRfc3339String()) as $remote) {
                $this->validateId($remote);
                $seen[$remote['id']] = true;
                $this->import($connection, $calendar, $remote);
                $count++;
            }
            // A missing page item is never evidence of deletion. GET known, in-window
            // events individually to catch cancellations and events moved outside the window.
            foreach (Event::where('provider_connection_id', $connection->id)->where('source', 'google_calendar')->whereBetween('starts_at', [$from, $to])->where('source_snapshot->google_calendar_id', $calendar['id'])->cursor() as $event) {
                $id = $event->source_snapshot['google_event_id'] ?? null;
                if (! $id || isset($seen[$id])) {
                    continue;
                }
                $remote = $this->client->event($connection, $calendar['id'], $id);
                $this->import($connection, $calendar, $remote ?? ['id' => $id, 'status' => 'cancelled']);
            }
        }

        return $count;
    }

    private function validateId(array $remote): void
    {
        if (! is_string($remote['id'] ?? null) || $remote['id'] === '' || strlen($remote['id']) > 1024) {
            throw new GoogleCalendarException('Google returned an event without a valid identifier. Retry the import.');
        }
    }

    private function canonical(array $remote, array $calendar): array
    {
        try {
            $allDay = isset($remote['start']['date']);
            $zone = $this->client->timezone($remote['start']['timeZone'] ?? $calendar['timezone']);
            $start = $remote['start'][$allDay ? 'date' : 'dateTime'] ?? null;
            $end = $remote['end'][$allDay ? 'date' : 'dateTime'] ?? null;
            if (! is_string($start) || ! is_string($end) || ($allDay && (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)))) {
                throw new \RuntimeException;
            }
            if (! $allDay && (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $start) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $end))) {
                throw new \RuntimeException;
            }
            $starts = CarbonImmutable::parse($start, $zone)->utc();
            $ends = CarbonImmutable::parse($end, $zone)->utc();
            if ($ends->lessThanOrEqualTo($starts)) {
                throw new \RuntimeException;
            }
        } catch (Throwable) {
            throw new GoogleCalendarException('Google returned an event with invalid dates. This event was not imported.');
        }

        return ['title' => mb_substr(strip_tags(is_string($remote['summary'] ?? null) ? $remote['summary'] : 'Untitled Google event'), 0, 255) ?: 'Untitled Google event', 'description' => strip_tags(is_string($remote['description'] ?? null) ? $remote['description'] : ''), 'location' => is_string($remote['location'] ?? null) ? $remote['location'] : null, 'starts_at' => $starts->toIso8601String(), 'ends_at' => $ends->toIso8601String(), 'timezone' => $zone, 'all_day' => $allDay, 'recurrence' => null, 'status' => 'draft'];
    }

    private function import(ProviderConnection $connection, array $calendar, array $remote): void
    {
        $this->validateId($remote);
        DB::transaction(function () use ($connection, $calendar, $remote) {
            $active = ProviderConnection::whereKey($connection->id)->lockForUpdate()->firstOrFail();
            if (in_array($active->status, ['disconnected', 'reconnect_required']) || ($active->settings['revision'] ?? null) !== ($connection->settings['revision'] ?? null)) {
                throw new GoogleCalendarException('Google connection changed during import. Run a new sync.');
            }
            $external = hash('sha256', $calendar['id']."\0".$remote['id']);
            $event = Event::withTrashed()->where('provider_connection_id', $connection->id)->where('external_id', $external)->lockForUpdate()->first();
            if ($event?->trashed()) {
                return;
            }
            $cancelled = ($remote['status'] ?? null) === 'cancelled';
            if ($cancelled && ! $event) {
                return;
            }
            if ($cancelled) {
                $snapshot = array_replace($event->source_snapshot ?? [], ['status' => 'cancelled', 'google_status' => 'cancelled']);
                $updates = ['source_snapshot' => $snapshot, 'synced_at' => now()];
                if (! in_array('status', $event->local_overrides ?? [])) {
                    $updates['status'] = 'cancelled';
                }
                $event->forceFill($updates)->save();

                return;
            }
            if (! in_array($remote['status'] ?? null, ['confirmed', 'tentative'])) {
                throw new GoogleCalendarException('Google returned an unexpected event status. Retry the import.');
            }
            $data = $this->canonical($remote, $calendar);
            $template = $active->template_id ? EventTemplate::findOrFail($active->template_id) : null;
            $mapped = [];
            $warnings = [];
            foreach (FieldTemplate::whereIn('id', $template?->fields ?? [])->get() as $field) {
                $source = ($active->field_mapping ?? [])[$field->field_id] ?? null;
                $value = $source ? ($data[$source] ?? null) : null;
                $type = match ($field->type) {
                    'number' => 'integer', 'decimal' => 'numeric', 'boolean' => 'boolean', 'date', 'datetime' => 'date', 'email' => 'email', 'url', 'image' => 'url', default => 'string',
                };
                $rules = [$field->required ? 'required' : 'nullable', $type];
                if ($field->type === 'select') {
                    $rules[] = Rule::in($field->options ?? []);
                }
                if (Validator::make(['value' => $value], ['value' => $rules])->fails()) {
                    $warnings[] = 'Complete or correct '.$field->label.' in EventNest.';
                    $value = null;
                }
                $mapped[$field->field_id] = $value;
            }
            $event ??= new Event;
            $updates = $data + ['template_id' => $template?->id, 'event_data' => $mapped];
            foreach ($event->local_overrides ?? [] as $key) {
                unset($updates[$key]);
            }
            $url = $remote['htmlLink'] ?? null;
            $event->forceFill($updates + ['source' => 'google_calendar', 'provider_connection_id' => $connection->id, 'external_id' => $external, 'source_url' => is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : null, 'synced_at' => now(), 'source_snapshot' => $data + ['google_calendar_id' => $calendar['id'], 'google_event_id' => $remote['id'], 'google_recurring_event_id' => $remote['recurringEventId'] ?? null, 'google_status' => $remote['status'], 'mapping_warnings' => $warnings]])->save();
        });
    }
}
