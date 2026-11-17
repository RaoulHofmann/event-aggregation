<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Traits\SchemaManagement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTeams extends Command
{
    use SchemaManagement;

    protected $signature = 'teams:migrate';

    protected $description = 'Apply event migrations to existing workspaces';

    public function handle(): int
    {
        DB::select("SELECT set_config('search_path', 'public', false)");
        foreach (Team::orderBy('id')->cursor() as $team) {
            if (! $this->isTenantSchema($team->schema_name)) {
                $this->error('Workspace '.$team->id.' needs schema recovery.');

                return self::FAILURE;
            }
            $this->runMigrations($team->schema_name);
            $this->info('Migrated workspace '.$team->id);
        }

        return self::SUCCESS;
    }
}
