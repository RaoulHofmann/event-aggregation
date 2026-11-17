<?php

namespace App\Integrations;

use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\ProviderConnection;
use Illuminate\Support\Facades\DB;

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
                $template ??= $this->template($connection);
                $data = ['title' => $remote['name'], 'description' => strip_tags($remote['description'] ?? ''), 'starts_at' => $remote['startDate'] ?? null, 'ends_at' => $remote['endDate'] ?? null, 'timezone' => $remote['timezone'] ?? null, 'location' => $remote['eventLocation']['venueName'] ?? $remote['eventLocation']['address'] ?? $remote['eventLocation']['onlineUrl'] ?? null, 'status' => ($remote['published'] ?? false) ? 'published' : 'draft'];
                $event = Event::withTrashed()->firstOrNew(['provider_connection_id' => $connection->id, 'external_id' => $remote['_id']]);
                if ($event->exists && $event->trashed()) {
                    return;
                }
                $event->fill(['template_id' => $template->id, 'event_data' => array_combine(array_map(fn ($key) => 'humanitix_'.$key, array_keys($data)), array_values($data))]);
                $event->forceFill($data + ['source' => 'humanitix', 'provider_connection_id' => $connection->id, 'external_id' => $remote['_id'], 'source_url' => isset($remote['url']) && filter_var($remote['url'], FILTER_VALIDATE_URL) && str_starts_with($remote['url'], 'https://') ? $remote['url'] : null, 'synced_at' => now()])->save();
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
