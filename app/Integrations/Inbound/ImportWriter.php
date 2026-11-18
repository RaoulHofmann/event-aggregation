<?php

namespace App\Integrations\Inbound;

use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\ProviderConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportWriter
{
    /** @param list<ImportedEvent> $events */
    public function write(ProviderConnection $connection, array $events, ?int $templateId = null, array $mapping = []): int
    {
        return DB::transaction(function () use ($connection, $events, $templateId, $mapping) {
            $active = ProviderConnection::whereKey($connection->id)->lockForUpdate()->firstOrFail();
            if (in_array($active->status, ['disconnected', 'reconnect_required']) || ($active->settings['revision'] ?? null) !== ($connection->settings['revision'] ?? null)) {
                throw new ImportException('The connection changed during import. Retry with the current settings.');
            }
            if ($active->provider !== 'csv') {
                $templateId = $active->template_id;
                $mapping = $active->field_mapping ?? [];
            }
            $template = $templateId ? EventTemplate::findOrFail($templateId) : null;
            $fields = FieldTemplate::whereIn('id', $template?->fields ?? [])->get();
            $count = 0;

            foreach ($events as $remote) {
                $event = Event::withTrashed()->where('provider_connection_id', $active->id)
                    ->where('external_id', $remote->key)->lockForUpdate()->first();

                if ($event?->trashed() || ($remote->cancelled && ! $event)) {
                    continue;
                }
                if ($remote->cancelled) {
                    $updates = ['source_snapshot' => array_replace($event->source_snapshot ?? [], ['status' => 'cancelled']), 'synced_at' => now()];
                    if (! in_array('status', $event->local_overrides ?? [], true)) {
                        $updates['status'] = 'cancelled';
                    }
                    $event->forceFill($updates)->save();
                    $count++;

                    continue;
                }

                $event ??= new Event;
                $custom = [];
                $warnings = [];
                $sourceValues = $remote->fields + $remote->metadata + ['source_url' => $remote->url];
                foreach ($fields as $field) {
                    $source = $mapping[$field->field_id] ?? null;
                    $value = $sourceValues[$source] ?? null;
                    $rules = [
                        $field->required ? 'required' : 'nullable',
                        match ($field->type) {
                            'number' => 'integer',
                            'decimal' => 'numeric',
                            'boolean' => 'boolean',
                            'date', 'datetime' => 'date',
                            'email' => 'email',
                            'url', 'image' => 'url',
                            default => 'string',
                        },
                    ];
                    if ($field->type === 'select') {
                        $rules[] = Rule::in($field->options ?? []);
                    }
                    if (Validator::make(['value' => $value], ['value' => $rules])->fails()) {
                        $value = null;
                        $warnings[] = 'Complete or correct '.$field->label.' in EventJunction.';
                    }
                    $custom[$field->field_id] = $value;
                }

                $updates = $remote->fields + ['template_id' => $template?->id, 'event_data' => $custom];
                foreach ($event->local_overrides ?? [] as $key) {
                    unset($updates[$key]);
                }
                $event->forceFill($updates + [
                    'source' => $active->provider,
                    'provider_connection_id' => $active->id,
                    'external_id' => $remote->key,
                    'source_url' => $remote->url,
                    'synced_at' => now(),
                    'source_snapshot' => $remote->fields + $remote->metadata + ['mapping_warnings' => $warnings],
                ])->save();
                $count++;
            }

            $active->update(['status' => 'connected', 'last_synced_at' => now(), 'last_error' => null]);

            return $count;
        });
    }
}
