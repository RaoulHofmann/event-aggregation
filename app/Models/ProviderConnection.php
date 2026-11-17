<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderConnection extends Model
{
    protected $fillable = ['provider', 'name', 'credentials', 'status', 'last_synced_at', 'last_error'];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'last_synced_at' => 'datetime'];
    }
}
