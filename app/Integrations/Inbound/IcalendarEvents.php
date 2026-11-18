<?php

namespace App\Integrations\Inbound;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Sabre\VObject\Component;
use Sabre\VObject\Reader;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\NoInstancesException;
use Throwable;

class IcalendarEvents
{
    public function read(string $data, string $timezone): array
    {
        $fallback = new DateTimeZone($timezone);
        $from = CarbonImmutable::now('UTC')->subDays(90)->startOfDay();
        $to = CarbonImmutable::now('UTC')->addYear()->endOfDay();
        $rows = [];
        try {
            $calendar = Reader::read($data);
            if ($calendar->name !== 'VCALENDAR') {
                throw new ImportException('The address did not return an iCalendar feed.');
            }
            $events = $calendar->select('VEVENT');
            if (count($events) > 2000) {
                throw new ImportException('This feed exceeds the current calendar import limit.');
            }
            $groups = [];
            foreach ($events as $event) {
                if (! isset($event->UID) || trim((string) $event->UID) === '') {
                    throw new ImportException('A calendar event is missing its UID.');
                }
                if (preg_match('/(?:^|;)FREQ=(SECONDLY|MINUTELY|HOURLY)(?:;|$)/i', (string) ($event->RRULE ?? ''))) {
                    throw new ImportException('Calendar feeds can repeat daily or less often.');
                }
                $groups[(string) $event->UID][] = $event;
            }

            foreach ($groups as $uid => $components) {
                $series = collect($components)->contains(fn ($event) => isset($event->RRULE) || isset($event->RDATE) || isset($event->{'RECURRENCE-ID'}));
                if (! $series) {
                    foreach ($components as $event) {
                        if ($this->cancelled($event) || (isset($event->DTSTART) && $event->DTSTART->getDateTime($fallback) <= $to && $this->end($event, $fallback) >= $from)) {
                            $import = $this->normalise($event, (string) $uid, $fallback);
                            $rows[$import->key] = $import;
                        }
                    }

                    continue;
                }

                $expanded = [];
                foreach ($components as $component) {
                    if ($this->cancelled($component) && isset($component->{'RECURRENCE-ID'})) {
                        $key = $this->key($component, (string) $uid, $fallback);
                        $import = ImportedEvent::make($key, ['cancelled' => true]);
                        $rows[$import->key] = $import;
                    } else {
                        $expanded[] = $component;
                    }
                }
                if (! $expanded) {
                    continue;
                }
                try {
                    $iterator = new EventIterator($expanded, (string) $uid, $fallback);
                    $iterator->fastForward($from);
                    while ($iterator->valid() && $iterator->getDtStart() <= $to) {
                        $event = $iterator->getEventObject();
                        $key = $this->key($event, (string) $uid, $fallback);
                        $hash = hash('sha256', $key);
                        if (! isset($rows[$hash])) {
                            $rows[$hash] = $this->normalise($event, $key, $fallback, $iterator->getDtEnd());
                        }
                        if (count($rows) > 2000) {
                            throw new ImportException('This feed exceeds the current calendar import limit.');
                        }
                        $iterator->next();
                    }
                } catch (NoInstancesException) {
                    // A calendar may exclude every instance of a series.
                }
            }

            return array_values($rows);
        } catch (ImportException $e) {
            throw $e;
        } catch (Throwable) {
            throw new ImportException('The iCalendar feed could not be parsed or exceeds the recurrence limit.');
        }
    }

    private function key(Component $event, string $uid, DateTimeZone $fallback): string
    {
        $date = $event->{'RECURRENCE-ID'} ?? $event->DTSTART;

        return $uid.':'.($date->hasTime() ? $date->getDateTime($fallback)->format('U') : $date->getValue());
    }

    private function cancelled(Component $event): bool
    {
        return strtoupper((string) ($event->STATUS ?? '')) === 'CANCELLED';
    }

    private function end(Component $event, DateTimeZone $fallback): \DateTimeInterface
    {
        $start = $event->DTSTART->getDateTime($fallback);

        return isset($event->DTEND) ? $event->DTEND->getDateTime($fallback)
            : (isset($event->DURATION) ? $start->add($event->DURATION->getDateInterval()) : $start);
    }

    private function normalise(Component $event, string $key, DateTimeZone $fallback, ?\DateTimeInterface $end = null): ImportedEvent
    {
        if ($this->cancelled($event)) {
            return ImportedEvent::make($key, ['cancelled' => true]);
        }
        $start = $event->DTSTART->getDateTime($fallback);
        $allDay = ! $event->DTSTART->hasTime();
        $zone = $allDay ? $fallback->getName() : $start->getTimezone()->getName();
        $end ??= $this->end($event, $fallback);

        return ImportedEvent::make($key, [
            'title' => (string) ($event->SUMMARY ?? 'Untitled calendar event'),
            'description' => (string) ($event->DESCRIPTION ?? ''),
            'location' => (string) ($event->LOCATION ?? ''),
            'starts_at' => $allDay ? $start->format('Y-m-d') : $start,
            'ends_at' => $allDay ? $end->format('Y-m-d') : $end,
            'timezone' => $zone,
            'all_day' => $allDay,
        ]);
    }
}
