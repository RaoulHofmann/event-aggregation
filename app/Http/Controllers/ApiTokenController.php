<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Http\Controllers\Inertia\ApiTokenController as JetstreamTokenController;
use Laravel\Jetstream\Jetstream;

class ApiTokenController extends JetstreamTokenController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam;
        $workspaceNames = $user->allTeams()->pluck('name', 'id');
        $writable = $team && $user->belongsToTeam($team) && ! $team->suspended_at
            ? array_values(array_filter(ApiToken::WRITE_ABILITIES, fn (string $ability) => ApiToken::permits($user, $team, $ability)))
            : [];

        return Jetstream::inertia()->render($request, 'API/Index', [
            'tokens' => $user->tokens()->orderBy('name')->get()->map(fn (ApiToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities ?? [],
                'last_used_ago' => $token->last_used_at?->diffForHumans(),
                'can_reveal' => ApiToken::readOnly($token->abilities ?? []) && $token->getRawOriginal('recoverable_secret') !== null,
                'workspace_name' => $workspaceNames[$token->workspace_id] ?? null,
            ]),
            'availablePermissions' => ['read', 'events:read', ...$writable],
            'workspaceName' => $team?->name,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct'],
            'recoverable' => ['sometimes', 'boolean'],
        ]);
        $abilities = $this->abilities($data['permissions'] ?? ['read']);
        if (! $abilities) {
            throw ValidationException::withMessages(['permissions' => 'Choose at least one permission.']);
        }
        if (in_array('events:read', $abilities, true) && count($abilities) > 1) {
            throw ValidationException::withMessages(['permissions' => 'Create a separate events-only key for this permission.']);
        }
        $writes = array_intersect(ApiToken::WRITE_ABILITIES, $abilities);
        $workspaceScoped = $writes || in_array('events:read', $abilities, true);
        $recoverable = $data['recoverable'] ?? false;
        if ($recoverable && ! ApiToken::readOnly($abilities)) {
            throw ValidationException::withMessages(['recoverable' => 'Only read-only keys can have a saved copy.']);
        }

        $user = $request->user();
        $team = $user->currentTeam;
        if ($workspaceScoped) {
            abort_unless($team && $user->belongsToTeam($team) && ! $team->suspended_at, 403);
            foreach ($writes as $ability) {
                abort_unless(ApiToken::permits($user, $team, $ability), 403);
            }
        }

        $secret = DB::transaction(function () use ($user, $team, $data, $abilities, $workspaceScoped, $recoverable) {
            $token = $user->createToken($data['name'], $abilities);
            $token->accessToken->forceFill([
                'workspace_id' => $workspaceScoped ? $team->id : null,
                'recoverable_secret' => $recoverable ? $token->plainTextToken : null,
            ])->save();

            return $token->plainTextToken;
        });

        return $request->expectsJson()
            ? $this->secretResponse($secret, 201)
            : back()->with('flash', ['token' => $secret]);
    }

    public function update(Request $request, $tokenId)
    {
        $data = $request->validate(['permissions' => ['present', 'array'], 'permissions.*' => ['string', 'distinct']]);
        $abilities = $this->abilities($data['permissions']);

        DB::transaction(function () use ($request, $tokenId, $abilities) {
            $token = $request->user()->tokens()->lockForUpdate()->findOrFail($tokenId);
            $added = array_diff($abilities, $token->abilities ?? []);
            if (($token->workspace_id && $added) || array_intersect(['events:read', ...ApiToken::WRITE_ABILITIES], $added)) {
                throw ValidationException::withMessages(['permissions' => 'Create a new key to grant additional permissions.']);
            }
            $token->forceFill(['abilities' => $abilities]);
            if (! ApiToken::readOnly($abilities)) {
                $token->recoverable_secret = null;
            }
            $token->save();
        });

        return back(303);
    }

    public function destroy(Request $request, $tokenId)
    {
        $request->user()->tokens()->findOrFail($tokenId)->delete();

        return back(303);
    }

    public function reveal(Request $request, int $token)
    {
        $key = $request->user()->tokens()->findOrFail($token);
        abort_unless(ApiToken::readOnly($key->abilities ?? []) && $key->getRawOriginal('recoverable_secret') !== null, 404);
        abort_if($key->expires_at?->isPast(), 404);

        return $this->secretResponse($key->recoverable_secret);
    }

    private function abilities(array $requested): array
    {
        return array_values(array_unique([...Jetstream::validPermissions($requested), ...array_intersect(['events:read', ...ApiToken::WRITE_ABILITIES], $requested)]));
    }

    private function secretResponse(string $secret, int $status = 200)
    {
        return response()->json(['token' => $secret], $status, ['Cache-Control' => 'private, no-store']);
    }
}
