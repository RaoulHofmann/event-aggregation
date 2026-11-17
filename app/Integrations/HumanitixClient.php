<?php

namespace App\Integrations;

use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class HumanitixClient
{
    public function events(string $apiKey): Generator
    {
        for ($page = 1; $page <= 1000; $page++) {
            $response = Http::acceptJson()->withHeaders(['x-api-key' => $apiKey])->connectTimeout(10)->timeout(30)->withoutRedirecting()
                ->get('https://api.humanitix.com/v1/events', ['page' => $page, 'pageSize' => 100, 'inFutureOnly' => 'false']);
            if (! $response->successful()) {
                throw new HumanitixSyncException(match ($response->status()) {
                    401,403 => 'Humanitix access was rejected. Check the API key and network access.',429 => 'Humanitix rate limit reached. Retry later.',default => 'Humanitix could not be reached successfully. Retry later.'
                });
            }
            $payload = $response->json();
            $validator = Validator::make(is_array($payload) ? $payload : [], ['events' => ['present', 'array'], 'total' => ['required', 'integer', 'min:0'], 'page' => ['required', 'integer', 'in:'.$page], 'pageSize' => ['required', 'integer', 'min:1', 'max:100'], 'events.*._id' => ['required', 'string', 'max:255'], 'events.*.name' => ['required', 'string', 'max:255'], 'events.*.published' => ['required', 'boolean'], 'events.*.description' => ['nullable', 'string'], 'events.*.startDate' => ['nullable', 'date'], 'events.*.endDate' => ['nullable', 'date'], 'events.*.timezone' => ['nullable', 'timezone'], 'events.*.eventLocation' => ['nullable', 'array'], 'events.*.eventLocation.venueName' => ['nullable', 'string'], 'events.*.eventLocation.address' => ['nullable', 'string'], 'events.*.eventLocation.onlineUrl' => ['nullable', 'string']]);
            if ($validator->fails()) {
                throw new HumanitixSyncException('Humanitix returned an unexpected event format. This page was not imported.');
            }
            foreach ($payload['events'] as $event) {
                yield $event;
            }
            if ($page * $payload['pageSize'] >= $payload['total']) {
                return;
            }
            if (! count($payload['events'])) {
                throw new HumanitixSyncException('Humanitix returned an incomplete page. Retry the sync.');
            }
        }
        throw new HumanitixSyncException('This account exceeds the current sync limit.');
    }
}
