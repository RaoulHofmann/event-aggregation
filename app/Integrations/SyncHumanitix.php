<?php

namespace App\Integrations;

use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\ProviderConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SyncHumanitix
{
    public function __construct(private HumanitixClient $client) {}

    public function sync(ProviderConnection $connection): int
    {
        $template = null;
        $count = 0;
        foreach ($this->client->events($connection->credentials['api_key']) as $remote) {
            DB::transaction(function () use ($connection, $remote, &$template) {
                $active = ProviderConnection::whereKey($connection->id)->lockForUpdate()->firstOrFail();
                if ($active->status === 'disconnected') {
                    throw new HumanitixSyncException('This connection was disconnected.');
                }
                $template = $active->template_id ? EventTemplate::findOrFail($active->template_id) : $this->template($active);
                $data = ['title' => $remote['name'], 'description' => strip_tags($remote['description'] ?? ''), 'starts_at' => $remote['startDate'] ?? null, 'ends_at' => $remote['endDate'] ?? null, 'timezone' => $remote['timezone'] ?? null, 'location' => $remote['eventLocation']['venueName'] ?? $remote['eventLocation']['address'] ?? $remote['eventLocation']['onlineUrl'] ?? null, 'status' => ($remote['published'] ?? false) ? 'published' : 'draft'];
                $event = Event::withTrashed()->firstOrNew(['provider_connection_id' => $connection->id, 'external_id' => $remote['_id']]);
                if ($event->exists && $event->trashed()) {
                    return;
                }
                $mapped = [];
                $warnings = [];
                if ($active->template_id) {
                    $allowed = FieldTemplate::whereIn('id', $template->fields ?? [])->pluck('field_id')->all();
                    foreach ($active->field_mapping ?? [] as $field => $source) {
                        if (in_array($field, $allowed) && isset($data[$source])) {
                            $mapped[$field] = $data[$source];
                        }
                    }
                } else {
                    $mapped = array_combine(array_map(fn ($key) => 'humanitix_'.$key, array_keys($data)), array_values($data));
                }
                if ($active->template_id) {
                    foreach (FieldTemplate::whereIn('id', $template->fields ?? [])->get() as $field) {
                        $type = match ($field->type) {
                            'number' => 'integer', 'decimal' => 'numeric', 'boolean' => 'boolean', 'date', 'datetime' => 'date', 'email' => 'email', 'url', 'image' => 'url', default => 'string',
                        };
                        $rules = [$field->required ? 'required' : 'nullable', $type];
                        if ($field->type === 'select') {
                            $rules[] = Rule::in($field->options ?? []);
                        }
                        if (Validator::make(['value' => $mapped[$field->field_id] ?? null], ['value' => $rules])->fails()) {
                            $warnings[] = 'Complete or correct '.$field->label.' in EventJunction.';
                            $mapped[$field->field_id] = null;
                        }
                    }
                }
                $overrides = $event->local_overrides ?? [];
                $updates = $data + ['template_id' => $template->id, 'event_data' => $mapped];
                foreach ($overrides as $key) {
                    unset($updates[$key]);
                }
                $event->forceFill($updates + ['source_snapshot' => $data + ['mapping_warnings' => $warnings], 'source' => 'humanitix', 'provider_connection_id' => $connection->id, 'external_id' => $remote['_id'], 'source_url' => isset($remote['url']) && filter_var($remote['url'], FILTER_VALIDATE_URL) && str_starts_with($remote['url'], 'https://') ? $remote['url'] : null, 'synced_at' => now()])->save();
            });
            $count++;
        }

        return $count;
    }

    private function template(ProviderConnection $connection): EventTemplate
    {
        DB::select("SELECT pg_advisory_xact_lock(hashtext(current_schema()), hashtext('humanitix_template'))");
        $ids = [];
        foreach (['title' => 'text', 'description' => 'textarea', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'timezone' => 'text', 'location' => 'text', 'status' => 'text'] as $key => $type) {
            $field = FieldTemplate::firstOrCreate(['field_id' => 'humanitix_'.$key], ['label' => 'Humanitix '.str_replace('_', ' ', $key), 'type' => $type]);
            $field->forceFill(['system_field' => true])->save();
            $ids[] = $field->id;
        }

        return EventTemplate::firstOrCreate(['name' => 'Humanitix · '.$connection->id, 'source' => 'humanitix'], ['description' => 'Events synchronized from '.$connection->name, 'fields' => $ids, 'layout' => [$ids, []]]);
    }
}
