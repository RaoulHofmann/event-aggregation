<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use App\Traits\SchemaManagement;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Events\AddingTeam;
use Laravel\Jetstream\Jetstream;

class CreateTeam implements CreatesTeams
{
    use SchemaManagement;

    /**
     * Validate and create a new team for the given user.
     *
     * @param array<string, string> $input
     */
    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validateWithBag('createTeam');

        AddingTeam::dispatch($user);

        $teamName = $input['name'];

        // Check if team already exists
        if ($this->teamExists($teamName)) {
            throw new \Exception("Team '$teamName' already exists.");
        }

        // Generate the schema name
        $schemaName = str_replace(' ', '_', strtolower($teamName));

        // Create the team and assign schema
        $user->switchTeam($team = $user->ownedTeams()->create([
            'name' => $teamName,
            'schema_name' => $schemaName,
            'personal_team' => false,
        ]));

        // Create the schema and migration table if not exists
        $this->createSchema($schemaName);

        // Run migrations for this schema
        $this->runMigrations($schemaName);

        return $team;
    }
}

