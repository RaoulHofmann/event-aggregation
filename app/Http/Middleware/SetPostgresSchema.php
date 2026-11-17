<?php

namespace App\Http\Middleware;

use App\Traits\SchemaManagement;
use Closure;
use Illuminate\Support\Facades\DB;

class SetPostgresSchema
{
    use SchemaManagement;

    public function handle($request, Closure $next)
    {
        DB::select("SELECT set_config('search_path', 'public', false)");
        try {
            $user = $request->user();
            $team = $user?->currentTeam;
            abort_unless($team && $user->belongsToTeam($team), 403, 'Select a workspace you belong to.');
            abort_unless($this->isTenantSchema($team->schema_name), 403, 'Workspace not ready.');
            $this->setSchema($team->schema_name);

            return $next($request);
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }
    }
}
