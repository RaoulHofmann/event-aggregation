<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = ['template_id', 'event_data'];

    protected $casts = [
        'event_data' => 'array'
    ];

    public function template(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EventTemplate::class);
    }
    public function recurrenceRules(): HasMany
    {
        return $this->hasMany(RecurrenceRule::class);
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(Exclusion::class);
    }
}
