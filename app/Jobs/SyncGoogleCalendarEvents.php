<?php

namespace App\Jobs;

use App\Integrations\GoogleCalendarException;
use App\Integrations\GoogleReconnectRequired;
use App\Integrations\SyncGoogleCalendar;
use App\Models\ProviderConnection;
use App\Models\Team;
use App\Traits\SchemaManagement;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncGoogleCalendarEvents implements ShouldBeUnique, ShouldQueue
{
    use Queueable,SchemaManagement;

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

    public function handle(SyncGoogleCalendar $sync): void
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
                if (! $record || $record->provider !== 'google_calendar' || in_array($record->status, ['disconnected', 'reconnect_required', 'needs_configuration'])) {
                    return null;
                }
                $record->update(['status' => 'syncing', 'last_error' => null]);

                return $record;
            });
            if (! $record) {
                return;
            }
            try {
                $sync->sync($record);
                ProviderConnection::whereKey($record->id)->where('status', 'syncing')->where('settings->revision', $record->settings['revision'] ?? '')->update(['status' => 'connected', 'last_synced_at' => now(), 'last_error' => null]);
            } catch (Throwable $error) {
                $message = $error instanceof GoogleCalendarException ? $error->getMessage() : 'Sync could not complete. Check the connection and retry.';
                ProviderConnection::whereKey($record->id)->where('status', 'syncing')->where('settings->revision', $record->settings['revision'] ?? '')->update(['status' => $error instanceof GoogleReconnectRequired ? 'reconnect_required' : 'error', 'last_error' => $message]);
                if (! $error instanceof GoogleReconnectRequired) {
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
            if (! $team || $team->suspended_at || ! $this->isTenantSchema($team->schema_name)) {
                return;
            }
            $this->setSchema($team->schema_name);
            ProviderConnection::whereKey($this->connectionId)->where('provider', 'google_calendar')->whereIn('status', ['error', 'syncing'])->update(['status' => 'error', 'last_error' => 'Sync stopped after repeated attempts. Check the connection and try again.']);
        } finally {
            DB::select("SELECT set_config('search_path', 'public', false)");
        }
    }
}
