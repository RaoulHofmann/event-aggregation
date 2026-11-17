<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Traits\SchemaManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvisionTeam
{
    use SchemaManagement;

    public function create(User $user, string $name, bool $personal = false): Team
    {
        return DB::transaction(function () use ($user, $name, $personal) {
            $schema = 'team_'.str_replace('-', '', (string) Str::uuid());
            $team = $user->ownedTeams()->create(['name' => $name, 'schema_name' => $schema, 'personal_team' => $personal]);
            $this->createSchema($schema);
            $this->runMigrations($schema);
            $user->switchTeam($team);

            return $team;
        });
    }
}
