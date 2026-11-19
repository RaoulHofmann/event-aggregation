<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;

class AuthorizeApiWrite
{
    public function handle(Request $request, Closure $next, string $ability)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();
        abort_unless($request->bearerToken() && $token instanceof ApiToken && $token->workspace_id && in_array($ability, $token->abilities ?? [], true), 403, 'A workspace API key with this permission is required.');
        abort_unless(ApiToken::permits($user, $request->attributes->get('api_workspace'), $ability), 403, 'Your workspace role does not allow this operation.');

        return $next($request);
    }
}
