<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     title="Event Nest API",
 *     version="0.0.1",
 *     description="API First Event Aggregator",
 *     @OA\Contact(
 *         email="raoul@668558.xyz"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="apiKey",
 *      type="apiKey",
 *      in="header",
 *      name="Authorization"
 *  )
 *
 * @OA\Server(
 *     url="https://event-aggregator.fly.dev/api",
 *     description="API Server"
 * )
 *
 * @OA\Tag(
 *     name="Events",
 *     description="API Endpoints for Events"
 * )
 */
class SwaggerAnnotations
{

}
