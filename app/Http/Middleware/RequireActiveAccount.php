<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->isActive() === false && ! $request->routeIs('account.status', 'logout', 'terms.show', 'policy.show', 'robots', 'sitemap')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(403, 'This account does not have workspace access.');
            }

            return to_route('account.status');
        }

        return $next($request);
    }
}
