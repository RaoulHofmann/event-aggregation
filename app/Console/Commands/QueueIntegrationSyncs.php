<?php

namespace App\Console\Commands;

use App\Jobs\SyncGoogleCalendarEvents;
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

    protected $description = 'Queue active inbound event integrations';

    public function handle(): int
    {
        DB::select("SELECT set_config('search_path', 'public', false)");
        try {
            foreach (Team::orderBy('id')->cursor() as $team) {
                if (! $this->isTenantSchema($team->schema_name)) {
                    continue;
                }
                $this->setSchema($team->schema_name);
                foreach (ProviderConnection::whereIn('provider', ['humanitix', 'google_calendar'])->whereNotIn('status', ['disconnected', 'reconnect_required', 'needs_configuration'])->cursor() as $record) {
                    if ($record->provider === 'google_calendar') {
                        SyncGoogleCalendarEvents::dispatch($team->id, $record->id);
                    } else {
                        SyncHumanitixEvents::dispatch($team->id, $record->id);
                    }
                }
                DB::select("SELECT set_config('search_path', 'public', false)");
            }
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }

        return self::SUCCESS;
    }
}
