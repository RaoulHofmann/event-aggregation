<?php

namespace App\Integrations\Inbound;

class CsvEvents
{
    public const COLUMNS = ['title', 'starts_at', 'ends_at', 'timezone', 'location', 'description', 'all_day', 'external_id'];

    /** @return array{events: list<ImportedEvent>, errors: list<string>} */
    public function read(string $path, string $timezone): array
    {
        $file = fopen($path, 'rb');
        if (! $file) {
            throw new ImportException('The CSV file could not be read.');
        }

        try {
            $headers = fgetcsv($file, escape: '');
            if (! is_array($headers)) {
                throw new ImportException('The CSV file has no header.');
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0] ?? '');
            $headers = array_map(fn ($name) => strtolower(trim($name ?? '')), $headers);
            if (! in_array('title', $headers, true) || count(array_unique($headers)) !== count($headers) || in_array('', $headers, true)) {
                throw new ImportException('Include a title column and use unique, non-empty column names.');
            }

            $events = [];
            $errors = [];
            $seen = [];
            $row = 1;
            while (($values = fgetcsv($file, escape: '')) !== false) {
                $row++;
                if ($row > 501) {
                    throw new ImportException('Import at most 500 rows at a time.');
                }
                if ($values === [null]) {
                    continue;
                }
                if (count($headers) !== count($values)) {
                    $errors[] = "Row {$row}: Number of cells does not match the header.";

                    continue;
                }

                $data = array_intersect_key(array_combine($headers, array_map(fn ($value) => trim($value ?? ''), $values)), array_flip(self::COLUMNS));
                try {
                    $allDay = ($data['all_day'] ?? '') === '' ? false : filter_var($data['all_day'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($allDay === null) {
                        throw new ImportException('Use true or false in the all_day column.');
                    }
                    $data['all_day'] = $allDay;
                    $data['timezone'] = ($data['timezone'] ?? '') ?: $timezone;
                    if (! mb_check_encoding(implode('', $values), 'UTF-8')) {
                        throw new ImportException('Save the file as UTF-8.');
                    }
                    $explicit = $data['external_id'] ?? '';
                    ksort($data);
                    $event = ImportedEvent::make($explicit === '' ? 'row:'.hash('sha256', serialize($data)) : 'id:'.$explicit, $data);
                    if (isset($seen[$event->key])) {
                        throw new ImportException('Duplicate event in this file. Add a distinct external_id if both rows are intentional.');
                    }
                    $seen[$event->key] = true;
                    $events[] = $event;
                } catch (ImportException $error) {
                    $errors[] = "Row {$row}: ".$error->getMessage();
                }
            }

            return ['events' => $events, 'errors' => $errors];
        } finally {
            fclose($file);
        }
    }
}
