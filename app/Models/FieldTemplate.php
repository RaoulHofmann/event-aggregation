<?php

namespace App\Models;

use App\Traits\MultiTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FieldTemplate extends Model
{
    use SoftDeletes, MultiTenantModel;

    protected $fillable = [
        'label', 'field_id', 'type', 'required',
        'validation_rules', 'options'
    ];

    protected $guarded = ['system_field'];

    protected $casts = [
        'required' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array',
        'system_field' => 'boolean'
    ];
}
