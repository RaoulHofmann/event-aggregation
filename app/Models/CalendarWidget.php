<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarWidget extends Model
{
    protected $table = 'public.calendar_widgets';

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
