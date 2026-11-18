<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'EventJunction API', version: '1.0.0', description: 'Native and imported workspace events')]
#[OA\SecurityScheme(securityScheme: 'apiKey', type: 'http', scheme: 'bearer', bearerFormat: 'Sanctum token')]
#[OA\Server(url: '/', description: 'Current EventJunction server')]
#[OA\Schema(schema: 'CanonicalEvent', type: 'object', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'title', type: 'string'), new OA\Property(property: 'description', type: 'string', nullable: true), new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true), new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true), new OA\Property(property: 'timezone', type: 'string', nullable: true), new OA\Property(property: 'location', type: 'string', nullable: true), new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'cancelled']), new OA\Property(property: 'source', type: 'object', properties: [new OA\Property(property: 'provider', type: 'string', enum: ['eventnest', 'humanitix', 'google_calendar']), new OA\Property(property: 'external_id', type: 'string', nullable: true), new OA\Property(property: 'url', type: 'string', nullable: true)]), new OA\Property(property: 'custom_fields', type: 'object')])]
class SwaggerAnnotations
{
    #[OA\Get(path: '/api/v1/events', summary: 'List native and imported workspace events', security: [['apiKey' => []]], parameters: [new OA\Parameter(name: 'source', in: 'query', schema: new OA\Schema(type: 'string', enum: ['eventnest', 'humanitix', 'google_calendar'])), new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'cancelled'])), new OA\Parameter(name: 'starts_after', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')), new OA\Parameter(name: 'starts_before', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')), new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)), new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 25))], responses: [new OA\Response(response: 200, description: 'Paginated events', content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CanonicalEvent')), new OA\Property(property: 'meta', type: 'object'), new OA\Property(property: 'links', type: 'object')])), new OA\Response(response: 401, description: 'Authentication required'), new OA\Response(response: 403, description: 'Read permission and workspace membership required'), new OA\Response(response: 422, description: 'Invalid filters'), new OA\Response(response: 429, description: 'Rate limit exceeded')])]
    public function events() {}

    #[OA\Get(path: '/api/events', summary: 'Legacy template-grouped event listing', security: [['apiKey' => []]], responses: [new OA\Response(response: 200, description: 'Legacy event response')])]
    public function legacy() {}
}
