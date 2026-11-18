<?php

namespace App\Console\Commands;

use App\Jobs\SyncGoogleCalendarEvents;
use App\Jobs\SyncHumanitixEvents;
use App\Jobs\SyncInboundEvents;
use App\Integrations\Inbound\InboundSources;
use App\Models\ProviderConnection;
use App\Models\Team;
use App\Traits\SchemaManagement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QueueIntegrationSyncs extends Command
{
    use SchemaManagement;

    protected $signature = 'integrations:sync';

    protected $description = 'Queue active inbound event integrations';

    public function handle(): int
    {
        DB::select("SELECT set_config('search_path', 'public', false)");
        try {
            foreach (Team::whereNull('suspended_at')->orderBy('id')->cursor() as $team) {
                if (! $this->isTenantSchema($team->schema_name)) {
                    continue;
                }
                $this->setSchema($team->schema_name);
                foreach (ProviderConnection::whereIn('provider', ['humanitix', 'google_calendar', ...InboundSources::SCHEDULED])->whereNotIn('status', ['disconnected', 'reconnect_required', 'needs_configuration'])->cursor() as $record) {
                    if ($record->provider === 'google_calendar') {
                        SyncGoogleCalendarEvents::dispatch($team->id, $record->id);
                    } elseif ($record->provider === 'humanitix') {
                        SyncHumanitixEvents::dispatch($team->id, $record->id);
                    } else {
                        SyncInboundEvents::dispatch($team->id, $record->id);
                    }
                }
                DB::select("SELECT set_config('search_path', 'public', false)");
            }
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }

        Cache::put('eventjunction:integration-scheduler:last-completed', now()->toIso8601String(), now()->addDays(7));

        return self::SUCCESS;
    }
}
