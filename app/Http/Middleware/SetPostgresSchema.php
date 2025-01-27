<?php

namespace App\Http\Middleware;

use App\Traits\SchemaManagement;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetPostgresSchema
{
    use SchemaManagement;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Log::info("Setting schema - Request path: " . $request->path());

        if (Auth::check()) {
            $user = Auth::user();
            $team = $user->currentTeam;

            if ($team) {
                Log::info("Setting schema to: " . $team->schema_name);
                $this->setSchema($team->schema_name);

                // Let's verify the schema was set
                $currentSchema = DB::select("SHOW search_path")[0]->search_path;
                Log::info("Current schema is now: " . $currentSchema);
            }
        }

        return $next($request);
    }
}
