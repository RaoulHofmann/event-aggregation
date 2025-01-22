<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exclusion extends Model
{
    protected $fillable = [
        'event_id',
        'excluded_date',
    ];

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
