<?php

namespace App\Services;

use App\Models\Event;
use Carbon\CarbonImmutable as Date;
use Illuminate\Support\Facades\DB;

class EventOccurrences
{
    // Local dates determine identity; UTC instants determine overlap. No scheduler is needed.
    public function forEvent(Event $event, Date $from, Date $to, bool $ignoreExceptions = false, ?int $workspace = null): array
    {
        if (! $event->starts_at) {
            return [];
        }
        $zone = $event->timezone ?: 'UTC';
        $start = Date::instance($event->starts_at)->setTimezone($zone);
        $end = $event->ends_at ? Date::instance($event->ends_at)->setTimezone($zone) : $start;
        $rule = $event->recurrence;
        $exceptions = $rule && ! $ignoreExceptions ? DB::table('event_exceptions')->where('event_id', $event->id)->get()->keyBy('occurrence_date') : collect();
        $results = [];
        $matched = 0;
        $until = $rule && ! empty($rule['until']) ? Date::parse($rule['until'], $zone)->endOfDay() : null;
        $imageUrl = $event->publishedCoverUrl($workspace);
        // Each series is explicitly finite (at most 1000 occurrences).
        foreach ($this->candidates($start, $rule) as $candidate) {
            if ($candidate->format('H:i:s') !== $start->format('H:i:s')) {
                continue; // Skip local wall-clock times that do not exist at a DST transition.
            }
            if ($until && $candidate > $until) {
                break;
            }
            if (++$matched > ($rule['count'] ?? 1000)) {
                break;
            }
            $date = $candidate->toDateString();
            $exception = $exceptions->get($date);
            if ($exception?->cancelled) {
                continue;
            }
            $finish = $event->all_day
                ? $candidate->addDays((int) $start->startOfDay()->diffInDays($end->startOfDay()))
                : $candidate->addSeconds((int) $start->diffInSeconds($end));
            $actualStart = $exception?->starts_at ? Date::parse($exception->starts_at)->setTimezone($zone) : $candidate;
            $actualEnd = $exception?->ends_at ? Date::parse($exception->ends_at)->setTimezone($zone) : $finish;
            if ($actualStart >= $to || ($actualEnd > $actualStart ? $actualEnd <= $from : $actualStart < $from)) {
                continue;
            }
            $results[] = [
                'id' => $event->id.'@'.$date, 'event_id' => $event->id, 'occurrence_date' => $date,
                'title' => $event->title ?: ($event->event_data['title'] ?? $event->event_data['name'] ?? 'Untitled event'),
                'starts_at' => $actualStart->utc()->toIso8601String(), 'ends_at' => $actualEnd->utc()->toIso8601String(),
                'start_date' => $actualStart->toDateString(), 'end_date' => $actualEnd->toDateString(),
                'timezone' => $zone, 'all_day' => $event->all_day, 'source' => $event->source,
                'status' => $event->status, 'template_id' => $event->template_id, 'recurring' => (bool) $rule,
                'image_url' => $imageUrl,
                'local_overrides' => $event->local_overrides ?? [], 'mapping_warnings' => $event->source_snapshot['mapping_warnings'] ?? [], 'custom_fields' => $event->event_data, 'location' => $event->location, 'description' => $event->description, 'source_url' => $event->source_url,
            ];
        }

        return $results;
    }

    private function candidates(Date $start, ?array $rule): \Generator
    {
        if (! $rule) {
            yield $start;

            return;
        }
        $interval = $rule['interval'] ?? 1;
        $frequency = $rule['frequency'];
        if ($frequency === 'weekly') {
            $days = $rule['weekdays'] ?? [$start->dayOfWeekIso];
            $week = $start->startOfWeek();
            for ($i = 0; $i < 1000; $i++) {
                foreach (range(1, 7) as $day) {
                    if (! in_array($day, $days)) {
                        continue;
                    }
                    $date = $week->addWeeks($i * $interval)->addDays($day - 1)->setTimeFrom($start);
                    if ($date >= $start) {
                        yield $date;
                    }
                }
            }

            return;
        }
        for ($i = 0; $i < 1000; $i++) {
            if ($frequency === 'daily') {
                yield $start->addDays($i * $interval);
            } elseif ($frequency === 'monthly') {
                $month = $start->startOfMonth()->addMonths($i * $interval);
                if (($rule['monthly_mode'] ?? 'date') === 'weekday') {
                    $position = (int) ceil($start->day / 7);
                    $date = $month->addDays(($start->dayOfWeekIso - $month->dayOfWeekIso + 7) % 7 + ($position - 1) * 7);
                    if ($date->month === $month->month) {
                        yield $date->setTimeFrom($start);
                    }
                } elseif ($start->day <= $month->daysInMonth) {
                    yield $month->day($start->day)->setTimeFrom($start);
                }
            } else {
                $year = $start->year + $i * $interval;
                if (checkdate($start->month, $start->day, $year)) {
                    yield $start->setDate($year, $start->month, $start->day);
                }
            }
        }
    }

    public function window(Date $from, Date $to, array $filters = [], ?int $workspace = null): array
    {
        $events = Event::whereNotNull('starts_at')->where(function ($q) use ($from, $to) {
            $q->whereNotNull('recurrence')->orWhere(function ($q) use ($from, $to) {
                $q->where('starts_at', '<', $to)->where(fn ($q) => $q->where('ends_at', '>', $from)->orWhere('starts_at', '>=', $from));
            });
        })->when($filters['search'] ?? null, fn ($q, $v) => $q->where('title', 'ilike', '%'.$v.'%'))
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filters['template'] ?? null, fn ($q, $v) => $q->where('template_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->limit(501)->get();
        abort_if($events->count() > 500, 422, 'Too many events for this calendar. Narrow the date range or use the table.');
        $items = [];
        foreach ($events as $event) {
            array_push($items, ...$this->forEvent($event, $from, $to, workspace: $workspace));
            abort_if(count($items) > 5000, 422, 'Too many occurrences. Narrow the date range or filters.');
        }
        usort($items, fn ($a, $b) => strcmp($a['starts_at'], $b['starts_at']));

        return $items;
    }
}
