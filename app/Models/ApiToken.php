<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

class ApiToken extends PersonalAccessToken
{
    public const WRITE_ABILITIES = ['events:create', 'events:update', 'templates:create', 'templates:update'];

    protected $table = 'public.personal_access_tokens';

    protected $hidden = ['token', 'recoverable_secret'];

    protected function casts(): array
    {
        return parent::casts() + ['recoverable_secret' => 'encrypted'];
    }

    public static function readOnly(array $abilities): bool
    {
        return $abilities === ['read'] || $abilities === ['events:read'];
    }

    public static function permits(User $user, Team $team, string $ability): bool
    {
        if ($user->ownsTeam($team)) {
            return true;
        }
        if (str_starts_with($ability, 'templates:')) {
            return $user->teamRole($team)?->key === 'admin';
        }

        return in_array(substr($ability, strlen('events:')), $user->teamPermissions($team), true);
    }
}
