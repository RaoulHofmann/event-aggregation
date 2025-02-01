<?php

namespace App\Models;

use App\Traits\MultiTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes, MultiTenantModel;

    protected $fillable = ['template_id', 'event_data'];

    protected $casts = [
        'event_data' => 'array'
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EventTemplate::class);
    }
}
