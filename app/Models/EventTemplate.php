<?php

namespace App\Models;

use App\Traits\MultiTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTemplate extends Model
{
    use MultiTenantModel, SoftDeletes;

    protected $fillable = ['name', 'description', 'fields', 'layout', 'source'];

    protected $casts = [
        'fields' => 'array',
        'layout' => 'array',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
