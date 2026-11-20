<?php

return [
    'url' => env('PUBLIC_SITE_URL', env('APP_URL', 'http://localhost')),
    'contact_email' => env('EVENTJUNCTION_LEGAL_EMAIL'),
    'legal_updated' => '18 November 2026',
    'privacy_updated' => '20 November 2026',
    'pages' => [
        'home' => [
            'title' => 'EventJunction — Event Calendar and Management Software',
            'description' => 'Event calendar software for teams. Create recurring events, import from calendars and ticketing platforms, and publish event listings through one API.',
        ],
        'pricing' => [
            'title' => 'Pricing — EventJunction',
            'description' => 'EventJunction is currently free during early access. See what is included and how future pricing will be announced.',
        ],
        'docs.api' => [
            'title' => 'API documentation — EventJunction',
            'description' => 'Read native and imported events from one workspace using the EventJunction API. Find authentication instructions, endpoints, and request examples.',
        ],
        'terms.show' => [
            'title' => 'Terms of Service — EventJunction',
            'description' => 'Read the terms for using EventJunction and its event workspace, integrations, and API.',
        ],
        'policy.show' => [
            'title' => 'Privacy Policy — EventJunction',
            'description' => 'Learn how EventJunction handles account, workspace, and integration data, including information imported from Google Calendar.',
        ],
    ],
];
