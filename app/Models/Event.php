<?php

namespace App\Models;

use App\Support\PublicSite;
use App\Traits\MultiTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use MultiTenantModel, SoftDeletes;

    protected $fillable = ['template_id', 'event_data'];

    protected $hidden = ['cover_public_id'];

    protected $casts = [
        'local_overrides' => 'array', 'source_snapshot' => 'array', 'all_day' => 'boolean', 'recurrence' => 'array', 'event_data' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'synced_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EventTemplate::class);
    }

    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(WorkspaceImage::class, 'cover_image_id');
    }

    public function publishedCoverUrl(?int $workspace): ?string
    {
        if (! $workspace || $this->status !== 'published' || ! $this->cover_image_id || ! $this->cover_public_id) {
            return null;
        }

        return PublicSite::url(route('events.cover', ['workspace' => $workspace, 'cover' => $this->cover_public_id], absolute: false));
    }
}
