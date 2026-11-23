<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'title' => $this->title ?? $this->event_data['title'] ?? $this->event_data['name'] ?? 'Event #'.$this->id, 'description' => $this->description, 'starts_at' => $this->starts_at?->toIso8601String(), 'ends_at' => $this->ends_at?->toIso8601String(), 'timezone' => $this->timezone, 'all_day' => $this->all_day, 'recurrence' => $this->recurrence, 'location' => $this->location, 'status' => $this->status,
            'image_url' => $this->resource->publishedCoverUrl($request->attributes->get('api_workspace')?->id),
            'source' => ['provider' => $this->source, 'local_overrides' => $this->local_overrides ?? [], 'external_id' => $this->external_id, 'url' => $this->source_url, 'synced_at' => $this->synced_at?->toIso8601String()],
            'template' => ['id' => $this->template_id, 'name' => $this->template?->name], 'custom_fields' => $this->event_data, 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
