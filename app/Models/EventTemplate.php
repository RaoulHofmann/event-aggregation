<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'field_configurations'];

    protected $casts = [
        'field_configurations' => 'array'
    ];

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class);
    }
}
