<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait MultiTenantModel
{
    use SchemaManagement;

    public function resolveRouteBinding($value, $field = null)
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            $this->setSchema(Auth::user()->currentTeam->schema_name);
        }
        return $this->where($field ?? $this->getRouteKeyName(), $value)->first();
    }
}
