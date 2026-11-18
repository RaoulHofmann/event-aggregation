<?php

namespace App\Jobs;

use App\Integrations\Inbound\EventbriteEvents;
use App\Integrations\Inbound\IcalendarEvents;
use App\Integrations\Inbound\ImportException;
use App\Integrations\Inbound\ImportWriter;
use App\Integrations\Inbound\InboundSources;
use App\Integrations\Inbound\PublicFeedClient;
use App\Integrations\Inbound\ReconnectRequired;
use App\Integrations\Inbound\WordPressEvents;
use App\Models\ProviderConnection;
use App\Models\Team;
use App\Traits\SchemaManagement;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncInboundEvents implements ShouldBeUnique, ShouldQueue
{
    use Queueable, SchemaManagement;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 3600;

    public function __construct(public int $teamId, public int $connectionId) {}

    public function uniqueId(): string
    {
        return $this->teamId.':'.$this->connectionId;
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(ImportWriter $writer): void
    {
        DB::select("SELECT set_config('search_path', 'public', false)");
        try {
            $team = Team::find($this->teamId);
            if (! $team || $team->suspended_at || ! $this->isTenantSchema($team->schema_name)) {
                return;
            }
            $this->setSchema($team->schema_name);
            $record = DB::transaction(function () {
                $record = ProviderConnection::whereKey($this->connectionId)->lockForUpdate()->first();
                if (! $record || ! in_array($record->provider, InboundSources::SCHEDULED, true)
                    || in_array($record->status, ['disconnected', 'reconnect_required'], true)) {
                    return null;
                }
                $record->update(['status' => 'syncing', 'last_error' => null]);

                return $record;
            });
            if (! $record) {
                return;
            }

            try {
                $timezone = $record->settings['timezone'] ?? 'UTC';
                $credentials = $record->credentials ?? [];
                $events = match ($record->provider) {
                    'ics' => app(IcalendarEvents::class)->read(app(PublicFeedClient::class)->get($credentials['feed_url']), $timezone),
                    'wordpress' => app(WordPressEvents::class)->read($credentials['feed_url'], $timezone),
                    'eventbrite' => app(EventbriteEvents::class)->read($record->settings['organization_id'], $credentials['api_key']),
                };
                $writer->write($record, $events);
            } catch (Throwable $error) {
                $message = $error instanceof ImportException ? $error->getMessage() : 'Import could not complete. Check the connection and retry.';
                ProviderConnection::whereKey($record->id)->where('status', 'syncing')
                    ->where('settings->revision', $record->settings['revision'])
                    ->update(['status' => $error instanceof ReconnectRequired ? 'reconnect_required' : 'error', 'last_error' => $message]);

                if (! $error instanceof ReconnectRequired) {
                    throw new \RuntimeException($message);
                }
            }
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::select("SELECT set_config('search_path', 'public', false)");
        try {
            $team = Team::find($this->teamId);
            if ($team && ! $team->suspended_at && $this->isTenantSchema($team->schema_name)) {
                $this->setSchema($team->schema_name);
                ProviderConnection::whereKey($this->connectionId)->whereIn('provider', InboundSources::SCHEDULED)->where('status', 'syncing')
                    ->update(['status' => 'error', 'last_error' => 'Import stopped before completion. Retry the connection.']);
            }
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }
    }
}
