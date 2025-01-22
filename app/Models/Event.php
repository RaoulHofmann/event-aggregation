<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    // Define the fillable fields for mass assignment
    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'start_date',
        'end_date',
        'timezone',
        'venue_name',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'is_virtual',
        'virtual_url',
        'capacity',
        'registration_required',
        'registration_deadline',
        'price',
        'organizer_id',
        'organizer_name',
        'organizer_email',
        'custom_fields',
        'featured_image'
    ];

    // Cast attributes to the appropriate types
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'custom_fields' => 'array', // Cast custom_fields as an array
        'is_virtual' => 'boolean',
        'price' => 'decimal:2',
        'latitude' => 'decimal:8,6',  // Assuming you want 6 decimal precision for latitude
        'longitude' => 'decimal:9,6'   // Same for longitude
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(EventField::class);
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
