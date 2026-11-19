<?php

namespace App\Integrations\Inbound;

class InboundSources
{
    public const SCHEDULED = ['ics', 'wordpress', 'eventbrite'];

    public const MANUAL = ['csv', 'website'];

    public const EVENT_SOURCES = ['eventnest', 'humanitix', 'google_calendar', 'ics', 'csv', 'wordpress', 'eventbrite', 'website'];

    public const MAPPING = ['title', 'description', 'starts_at', 'ends_at', 'timezone', 'location', 'status'];
}
