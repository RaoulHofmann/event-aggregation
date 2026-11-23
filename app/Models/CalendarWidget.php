<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarWidget extends Model
{
    public const THEMES = [
        'paper' => 'Warm paper',
        'sage' => 'Soft sage',
        'lilac' => 'Lilac',
        'ink' => 'Midnight',
    ];

    public const APPEARANCE = [
        'theme' => 'paper',
        'brand' => null,
        'compact' => false,
        'show_description' => false,
        'show_credit' => true,
    ];

    protected $table = 'public.calendar_widgets';

    protected function casts(): array
    {
        return ['appearance' => 'array'];
    }

    public function appearanceSettings(): array
    {
        return ($this->appearance ?? []) + self::APPEARANCE;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
