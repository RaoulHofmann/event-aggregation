<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'fields', 'layout'];

    protected $casts = [
        'fields' => 'array',
        'layout' => 'array'
    ];

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class);
    }
}
