<?php

namespace App\Models;

use App\Traits\MultiTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use MultiTenantModel, SoftDeletes;

    protected $fillable = ['template_id', 'event_data'];

    protected $casts = [
        'local_overrides' => 'array', 'source_snapshot' => 'array', 'all_day' => 'boolean', 'recurrence' => 'array', 'event_data' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'synced_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EventTemplate::class);
    }
}
