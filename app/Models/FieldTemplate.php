<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldTemplate extends Model
{
    protected $fillable = [
        'label', 'field_id', 'type', 'required',
        'validation_rules', 'options'
    ];

    protected $casts = [
        'required' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array'
    ];
}
