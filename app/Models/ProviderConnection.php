<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderConnection extends Model
{
    protected $fillable = ['provider', 'name', 'credentials', 'status', 'last_synced_at', 'last_error', 'template_id', 'field_mapping', 'settings'];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'field_mapping' => 'array', 'credentials' => 'encrypted:array', 'last_synced_at' => 'datetime'];
    }
}
