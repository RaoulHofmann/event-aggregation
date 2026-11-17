<?php

namespace App\Console\Commands;

use App\Jobs\SyncHumanitixEvents;
use App\Models\ProviderConnection;
use App\Models\Team;
use App\Traits\SchemaManagement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueIntegrationSyncs extends Command
{
    use SchemaManagement;

    protected $signature = 'integrations:sync';

    protected $description = 'Queue active Humanitix connections';

    public function handle(): int
    {
        DB::select("SELECT set_config('search_path', 'public', false)");
        try {
            foreach (Team::orderBy('id')->cursor() as $team) {
                if (! $this->isTenantSchema($team->schema_name)) {
                    continue;
                }
                $this->setSchema($team->schema_name);
                foreach (ProviderConnection::where('provider', 'humanitix')->where('status', '!=', 'disconnected')->cursor() as $record) {
                    SyncHumanitixEvents::dispatch($team->id, $record->id);
                }
                DB::select("SELECT set_config('search_path', 'public', false)");
            }
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }

return self::SUCCESS;
    }
}
