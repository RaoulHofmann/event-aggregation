<?php

namespace App\Integrations\Inbound;

use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SourceTemplates
{
    public const DEFINITIONS = [
        'eventbrite' => [
            'source_url' => ['label' => 'Ticket link', 'type' => 'url'],
            'venue_name' => ['label' => 'Venue name', 'type' => 'text'],
            'capacity' => ['label' => 'Capacity', 'type' => 'number'],
        ],
        'wordpress' => [
            'source_url' => ['label' => 'Listing link', 'type' => 'url'],
            'organiser' => ['label' => 'Organiser', 'type' => 'text'],
            'categories' => ['label' => 'Categories', 'type' => 'text'],
        ],
    ];

    /** Called inside the connection save transaction. */
    public function create(string $provider, string $connectionName): array
    {
        DB::select("SELECT pg_advisory_xact_lock(hashtext(current_schema()), hashtext('inbound_source_templates'))");

        $providerName = $provider === 'eventbrite' ? 'Eventbrite' : 'WordPress';
        $base = $connectionName === $providerName ? $providerName.' events' : $providerName.' · '.Str::limit($connectionName, 150);
        $name = $base;
        $suffix = 2;
        while (EventTemplate::withTrashed()->where('name', $name)->exists()) {
            $name = $base.' ('.$suffix++.')';
        }

        $ids = [];
        $mapping = [];
        foreach (self::DEFINITIONS[$provider] as $source => $definition) {
            $field = FieldTemplate::create([
                'label' => $definition['label'].' · '.$name,
                'field_id' => $provider.'_'.$source.'_'.Str::ulid(),
                'type' => $definition['type'],
                'required' => false,
            ]);
            $ids[] = $field->id;
            $mapping[$field->field_id] = $source;
        }

        $template = EventTemplate::create([
            'name' => $name,
            'description' => 'Starter fields for events imported from '.$providerName.'.',
            'source' => 'eventnest',
            'fields' => $ids,
            'layout' => [[$ids[0], $ids[1]], [$ids[2]]],
        ]);

        return [$template->id, $mapping];
    }
}
