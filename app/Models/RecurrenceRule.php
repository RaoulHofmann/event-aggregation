<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurrenceRule extends Model
{
    protected $fillable = [
        'event_id',
        'frequency',
        'interval',
        'days_of_week',
        'day_of_month',
        'start_date',
        'end_date',
    ];

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
