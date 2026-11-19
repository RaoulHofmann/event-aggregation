<?php

namespace App\Http\Controllers\Api;

use App\Integrations\Inbound\InboundSources;
use OpenApi\Attributes as OA;

#[OA\Info(title: 'EventJunction API', version: '1.0.0', description: 'Use Authorize with an API key. Read-only keys are the default. Creating or updating records requires a workspace-scoped key with the corresponding write permission.')]
#[OA\SecurityScheme(securityScheme: 'apiKey', type: 'http', scheme: 'bearer', bearerFormat: 'Sanctum token')]
#[OA\Server(url: '/', description: 'Current EventJunction server')]
#[OA\Tag(name: 'Events')]
#[OA\Tag(name: 'Templates')]
#[OA\Tag(name: 'Account')]
#[OA\Tag(name: 'Legacy')]
#[OA\Components(
    parameters: [
        new OA\Parameter(parameter: 'Source', name: 'source', description: 'Provider key. Omit to include all sources. Native events use eventnest.', in: 'query', schema: new OA\Schema(type: 'string', enum: InboundSources::EVENT_SOURCES)),
        new OA\Parameter(parameter: 'Status', name: 'status', description: 'Use published for a public listing. Authenticated requests can also return drafts.', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'cancelled'])),
    ],
    responses: [
        new OA\Response(response: 'Unauthorized', description: 'Authentication required'),
        new OA\Response(response: 'Forbidden', description: 'The key or workspace role lacks permission, or the workspace is unavailable'),
        new OA\Response(response: 'NotFound', description: 'No matching record in the selected workspace'),
        new OA\Response(response: 'InvalidParameters', description: 'Invalid parameters or occurrence limits exceeded'),
        new OA\Response(response: 'RateLimited', description: 'Versioned requests are limited to 60 per minute per authenticated user'),
    ],
    schemas: [
        new OA\Schema(schema: 'EventWrite', type: 'object', required: ['title'], properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Summer workshop'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'location', type: 'string', nullable: true),
            new OA\Property(property: 'status', description: 'Required for PUT. POST defaults to draft.', type: 'string', enum: ['draft', 'published', 'cancelled']),
            new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'timezone', type: 'string', example: 'Australia/Perth'),
            new OA\Property(property: 'all_day', type: 'boolean', default: false),
            new OA\Property(property: 'recurrence', type: 'object', nullable: true, properties: [
                new OA\Property(property: 'frequency', type: 'string', enum: ['daily', 'weekly', 'monthly', 'yearly']),
                new OA\Property(property: 'interval', type: 'integer', minimum: 1, maximum: 100),
                new OA\Property(property: 'count', type: 'integer', minimum: 1, maximum: 1000),
                new OA\Property(property: 'until', type: 'string', format: 'date'),
                new OA\Property(property: 'weekdays', type: 'array', items: new OA\Items(type: 'integer', minimum: 1, maximum: 7)),
                new OA\Property(property: 'monthly_mode', type: 'string', enum: ['date', 'weekday']),
            ]),
            new OA\Property(property: 'template_id', type: 'integer', nullable: true),
            new OA\Property(property: 'custom_fields', type: 'object', additionalProperties: true, example: ['audience' => 'Adults']),
        ]),
        new OA\Schema(schema: 'TemplateWrite', type: 'object', required: ['name', 'fields', 'layout'], properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Workshop'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'fields', description: 'Custom field numeric IDs.', type: 'array', items: new OA\Items(type: 'integer'), example: [12]),
            new OA\Property(property: 'layout', description: 'Field IDs grouped by column. Each field appears once.', type: 'array', items: new OA\Items(type: 'array', items: new OA\Items(type: 'integer')), example: [[12], []]),
        ]),
        new OA\Schema(schema: 'FieldWrite', type: 'object', required: ['label', 'type'], properties: [
            new OA\Property(property: 'field_id', description: 'Required on POST. Stable key used in event custom_fields.', type: 'string', example: 'audience'),
            new OA\Property(property: 'label', type: 'string', example: 'Audience'),
            new OA\Property(property: 'type', type: 'string', enum: ['text', 'number', 'date', 'datetime', 'email', 'url', 'select', 'boolean', 'textarea', 'decimal', 'image']),
            new OA\Property(property: 'required', type: 'boolean', default: false),
            new OA\Property(property: 'options', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
            new OA\Property(property: 'validation_rules', type: 'array', items: new OA\Items(), nullable: true),
        ]),
        new OA\Schema(schema: 'CanonicalEvent', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'timezone', type: 'string', nullable: true),
            new OA\Property(property: 'location', type: 'string', nullable: true),
            new OA\Property(property: 'all_day', type: 'boolean'),
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'cancelled']),
            new OA\Property(property: 'recurrence', type: 'object', nullable: true, properties: [
                new OA\Property(property: 'frequency', type: 'string', enum: ['daily', 'weekly', 'monthly', 'yearly']),
                new OA\Property(property: 'interval', type: 'integer'),
                new OA\Property(property: 'count', type: 'integer'),
                new OA\Property(property: 'until', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'weekdays', type: 'array', items: new OA\Items(type: 'integer')),
                new OA\Property(property: 'monthly_mode', type: 'string', enum: ['date', 'weekday']),
            ]),
            new OA\Property(property: 'source', type: 'object', properties: [
                new OA\Property(property: 'provider', type: 'string', enum: InboundSources::EVENT_SOURCES),
                new OA\Property(property: 'external_id', type: 'string', nullable: true),
                new OA\Property(property: 'url', type: 'string', nullable: true),
                new OA\Property(property: 'local_overrides', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'synced_at', type: 'string', format: 'date-time', nullable: true),
            ]),
            new OA\Property(property: 'template', type: 'object', properties: [
                new OA\Property(property: 'id', type: 'integer', nullable: true),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]),
            new OA\Property(property: 'custom_fields', oneOf: [new OA\Schema(type: 'object', additionalProperties: true), new OA\Schema(type: 'array', items: new OA\Items())]),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        ]),
        new OA\Schema(schema: 'Occurrence', type: 'object', properties: [
            new OA\Property(property: 'id', description: 'Event ID and original local date. Rescheduling retains this ID.', type: 'string', example: '42@2026-11-21'),
            new OA\Property(property: 'event_id', type: 'integer'),
            new OA\Property(property: 'occurrence_date', type: 'string', format: 'date'),
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'starts_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'ends_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'start_date', type: 'string', format: 'date'),
            new OA\Property(property: 'end_date', description: 'Exclusive for all-day events.', type: 'string', format: 'date'),
            new OA\Property(property: 'timezone', type: 'string'),
            new OA\Property(property: 'all_day', type: 'boolean'),
            new OA\Property(property: 'recurring', type: 'boolean'),
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'cancelled']),
            new OA\Property(property: 'source', type: 'string', enum: InboundSources::EVENT_SOURCES),
            new OA\Property(property: 'source_url', type: 'string', nullable: true),
            new OA\Property(property: 'template_id', type: 'integer', nullable: true),
            new OA\Property(property: 'location', type: 'string', nullable: true),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'custom_fields', oneOf: [new OA\Schema(type: 'object', additionalProperties: true), new OA\Schema(type: 'array', items: new OA\Items())]),
            new OA\Property(property: 'mapping_warnings', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'local_overrides', type: 'array', items: new OA\Items(type: 'string')),
        ]),
    ],
)]
class SwaggerAnnotations
{
    #[OA\Get(
        path: '/api/v1/events',
        summary: 'List native and imported events',
        description: 'Returns event records and recurrence definitions. Use calendar occurrences for expanded dates.',
        tags: ['Events'],
        security: [['apiKey' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Source'),
            new OA\Parameter(ref: '#/components/parameters/Status'),
            new OA\Parameter(name: 'starts_after', in: 'query', description: 'Inclusive lower bound on the event start.', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'starts_before', in: 'query', description: 'Inclusive upper bound on the event start.', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 25)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated events', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CanonicalEvent')),
                new OA\Property(property: 'links', type: 'object'),
                new OA\Property(property: 'meta', type: 'object'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/InvalidParameters'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function events() {}

    #[OA\Get(
        path: '/api/v1/occurrences',
        summary: 'List calendar occurrences',
        description: 'Returns events overlapping a window of up to 93 days, sorted by start. Limited to 500 candidate events and 5,000 occurrences.',
        tags: ['Events'],
        security: [['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: true, description: 'ISO date or timestamp.', schema: new OA\Schema(type: 'string', example: '2026-11-01T00:00:00Z')),
            new OA\Parameter(name: 'to', in: 'query', required: true, description: 'Exclusive ISO date or timestamp.', schema: new OA\Schema(type: 'string', example: '2026-12-01T00:00:00Z')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search event titles.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'template', in: 'query', description: 'Event template ID.', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/Source'),
            new OA\Parameter(ref: '#/components/parameters/Status'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Calendar occurrences', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Occurrence')),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/InvalidParameters'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function occurrences() {}

    #[OA\Get(
        path: '/api/events',
        summary: 'List events using the legacy format',
        description: 'Returns records grouped by template name. Filtering by template returns a flat list. An empty result has HTTP 200 and a body status of 204. New clients should use /api/v1/events.',
        tags: ['Legacy'],
        security: [['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'template_id', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'template_name', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 1000)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Legacy response', content: new OA\JsonContent(type: 'object', additionalProperties: true)),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/InvalidParameters'),
        ],
    )]
    public function legacy() {}

    #[OA\Get(path: '/api/event-templates', summary: 'List event templates', tags: ['Templates'], security: [['apiKey' => []]], responses: [
        new OA\Response(response: 200, description: 'Event templates in the current workspace', content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'fields', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
            new OA\Property(property: 'layout', type: 'array', items: new OA\Items(type: 'array', items: new OA\Items(type: 'integer')), nullable: true),
            new OA\Property(property: 'source', type: 'string'),
        ]))),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
    ])]
    public function templates() {}

    #[OA\Get(path: '/api/field-templates', summary: 'List custom field definitions', tags: ['Templates'], security: [['apiKey' => []]], responses: [
        new OA\Response(response: 200, description: 'Field definitions in the current workspace', content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'field_id', description: 'Key used in custom_fields.', type: 'string'),
            new OA\Property(property: 'label', type: 'string'),
            new OA\Property(property: 'type', type: 'string'),
            new OA\Property(property: 'required', type: 'boolean'),
            new OA\Property(property: 'options', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
        ]))),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
    ])]
    public function fields() {}

    #[OA\Get(path: '/api/user', summary: 'Get the authenticated account', tags: ['Account'], security: [['apiKey' => []]], responses: [
        new OA\Response(response: 200, description: 'Account profile with the current workspace ID', content: new OA\JsonContent(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'current_team_id', type: 'integer'),
        ])),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
    ])]
    public function user() {}
    #[OA\Get(
        path: '/api/v1/events/{id}',
        summary: 'Get an event',
        description: 'Requires read or events:read.',
        tags: ['Events'],
        security: [['apiKey' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Workspace record', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/CanonicalEvent'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function showEvent() {}

    #[OA\Post(
        path: '/api/v1/events',
        summary: 'Create an event',
        description: 'Requires events:create. New events default to draft.',
        tags: ['Events'],
        security: [['apiKey' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EventWrite')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/CanonicalEvent'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/InvalidParameters'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function createEvent() {}

    #[OA\Put(
        path: '/api/v1/events/{id}',
        summary: 'Replace an event',
        description: 'Requires events:update. Send the complete editable record. An imported event is updated in EventJunction only.',
        tags: ['Events'],
        security: [['apiKey' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/EventWrite'), new OA\Schema(type: 'object', required: ['status'])])),
        responses: [
            new OA\Response(response: 200, description: 'Workspace record', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/CanonicalEvent'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/InvalidParameters'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function updateEvent() {}

    #[OA\Get(
        path: '/api/v1/event-templates/{id}',
        summary: 'Get a template',
        description: 'Requires read.',
        tags: ['Templates'],
        security: [['apiKey' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Workspace record', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/TemplateWrite'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function showTemplate() {}

    #[OA\Post(
        path: '/api/v1/event-templates',
        summary: 'Create a template',
        description: 'Requires templates:create.',
        tags: ['Templates'],
        security: [['apiKey' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TemplateWrite')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/TemplateWrite'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/InvalidParameters'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function createTemplate() {}

    #[OA\Put(
        path: '/api/v1/event-templates/{id}',
        summary: 'Replace a template',
        description: 'Requires templates:update. Send the complete editable record.',
        tags: ['Templates'],
        security: [['apiKey' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TemplateWrite')),
        responses: [
            new OA\Response(response: 200, description: 'Workspace record', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/TemplateWrite'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/InvalidParameters'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function updateTemplate() {}

    #[OA\Get(
        path: '/api/v1/field-templates/{id}',
        summary: 'Get a field',
        description: 'Requires read.',
        tags: ['Templates'],
        security: [['apiKey' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Workspace record', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/FieldWrite'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function showField() {}

    #[OA\Post(
        path: '/api/v1/field-templates',
        summary: 'Create a field',
        description: 'Requires templates:create.',
        tags: ['Templates'],
        security: [['apiKey' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/FieldWrite'), new OA\Schema(type: 'object', required: ['field_id'])])),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/FieldWrite'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/InvalidParameters'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function createField() {}

    #[OA\Put(
        path: '/api/v1/field-templates/{id}',
        summary: 'Replace a field',
        description: 'Requires templates:update. Send the complete editable record.',
        tags: ['Templates'],
        security: [['apiKey' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/FieldWrite')),
        responses: [
            new OA\Response(response: 200, description: 'Workspace record', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/FieldWrite'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/InvalidParameters'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimited'),
        ],
    )]
    public function updateField() {}

}
