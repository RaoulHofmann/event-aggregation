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
            abort_unless($user?->isActive(), 403, 'This account does not have workspace access.');
            $team = $user->currentTeam;
            abort_unless($team && $user->belongsToTeam($team), 403, 'Select a workspace you belong to.');
            abort_if($team->suspended_at, 403, 'This workspace is paused. Contact an administrator or choose another workspace.');
            abort_unless($this->isTenantSchema($team->schema_name), 403, 'Workspace not ready.');
            $this->setSchema($team->schema_name);

            return $next($request);
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }
    }
}
