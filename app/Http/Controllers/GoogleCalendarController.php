<?php

namespace App\Http\Controllers;

use App\Integrations\GoogleCalendarClient;
use App\Integrations\GoogleCalendarException;
use App\Integrations\GoogleReconnectRequired;
use App\Jobs\SyncGoogleCalendarEvents;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\ProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class GoogleCalendarController extends Controller
{
    private function owner(Request $request): void
    {
        abort_unless($request->user()->ownsTeam($request->user()->currentTeam), 403);
    }

    private function connection(int $id): ProviderConnection
    {
        return ProviderConnection::where('provider', 'google_calendar')->findOrFail($id);
    }

    public function disclosure(Request $request, GoogleCalendarClient $client)
    {
        $this->owner($request);
        abort_unless($client->configured(), 503, 'Google Calendar has not been configured.');
        $input = $request->validate(['connection' => ['nullable', 'integer']]);
        $connection = isset($input['connection']) ? $this->connection($input['connection']) : null;

        return Inertia::render('Integrations/GoogleCalendarConsent', ['connectionId' => $connection?->id]);
    }

    public function connect(Request $request, GoogleCalendarClient $client)
    {
        $this->owner($request);
        abort_unless($client->configured(), 503, 'Google Calendar has not been configured.');
        $input = $request->validate(['connection' => ['nullable', 'integer'], 'consent' => ['accepted']]);
        $connection = isset($input['connection']) ? $this->connection($input['connection']) : null;
        $state = Str::random(64);
        $verifier = Str::random(64);
        $request->session()->put('google_calendar_oauth', ['state' => $state, 'verifier' => $verifier, 'user_id' => $request->user()->id, 'team_id' => $request->user()->current_team_id, 'expires_at' => now()->addMinutes(10)->timestamp, 'connection_id' => $connection?->id, 'revision' => $connection?->settings['revision'] ?? null]);

        return Inertia::location('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query(['client_id' => config('services.google_calendar.client_id'), 'redirect_uri' => config('services.google_calendar.redirect_uri'), 'response_type' => 'code', 'scope' => implode(' ', GoogleCalendarClient::SCOPES), 'state' => $state, 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='), 'code_challenge_method' => 'S256', 'access_type' => 'offline', 'prompt' => 'consent', 'include_granted_scopes' => 'false']));
    }

    public function callback(Request $request, GoogleCalendarClient $client)
    {
        $this->owner($request);
        $flow = $request->session()->pull('google_calendar_oauth');
        abort_unless(is_array($flow) && is_string($request->query('state')) && hash_equals($flow['state'], $request->query('state')) && $flow['user_id'] === $request->user()->id && $flow['team_id'] === $request->user()->current_team_id && $flow['expires_at'] >= now()->timestamp, 403, 'This Google connection attempt expired or belongs to another workspace. Start again.');
        if ($request->has('error')) {
            return to_route('integrations.index')->with('error', 'Google connection was not completed. No calendars were connected.');
        }
        $code = $request->query('code');
        abort_unless(is_string($code) && $code !== '' && strlen($code) <= 8192, 422);
        try {
            $tokens = $client->exchange($code, $flow['verifier']);
            $calendars = $client->calendarsWithToken($tokens['access_token']);
            $primary = collect($calendars)->firstWhere('primary', true);
            if (! $primary) {
                throw new GoogleCalendarException('Google did not return a primary calendar. Check calendar permissions and reconnect.');
            }
            $record = DB::transaction(function () use ($flow, $tokens, $primary) {
                $record = $flow['connection_id'] ? ProviderConnection::where('provider', 'google_calendar')->whereKey($flow['connection_id'])->lockForUpdate()->firstOrFail() : new ProviderConnection(['provider' => 'google_calendar', 'name' => 'Google Calendar']);
                $settings = $record->settings ?? [];
                if ($record->exists && ($settings['revision'] ?? null) !== ($flow['revision'] ?? null)) {
                    throw new GoogleCalendarException('This connection changed during authorization. Start again.');
                }
                if ($record->exists && ($settings['account_id'] ?? null) !== $primary['id']) {
                    throw new GoogleCalendarException('Reconnect using the original Google account, or add a separate connection.');
                }
                $credentials = $record->credentials ?? [];
                if (empty($tokens['refresh_token']) && empty($credentials['refresh_token'])) {
                    throw new GoogleCalendarException('Google did not grant offline access. Remove EventJunction from your Google account permissions and connect again.');
                }
                $record->fill(['credentials' => array_replace($credentials, $tokens), 'settings' => array_replace($settings, ['account_id' => $primary['id'], 'revision' => (string) Str::uuid()]), 'status' => empty($settings['calendars']) ? 'needs_configuration' : 'connected', 'last_error' => null])->save();

                return $record;
            });
        } catch (GoogleCalendarException $error) {
            return to_route('integrations.index')->with('error', $error->getMessage());
        }

        return to_route('integrations.google.edit', $record->id)->with('success', 'Google connected. Choose the calendars and template to import.');
    }

    public function edit(Request $request, int $connection, GoogleCalendarClient $client)
    {
        $this->owner($request);
        $record = $this->connection($connection);
        if (in_array($record->status, ['disconnected', 'reconnect_required'])) {
            return to_route('integrations.index')->with('error', 'Reconnect this Google account first.');
        }
        try {
            $calendars = $client->calendars($record);
        } catch (GoogleCalendarException $error) {
            if ($error instanceof GoogleReconnectRequired) {
                $record->update(['status' => 'reconnect_required', 'last_error' => $error->getMessage()]);
            }

            return to_route('integrations.index')->with('error', $error->getMessage());
        }

        return Inertia::render('Integrations/GoogleCalendar', ['connection' => $record, 'calendars' => $calendars, 'eventTemplates' => EventTemplate::where('source', 'eventnest')->orderBy('name')->get(), 'fieldTemplates' => FieldTemplate::orderBy('label')->get()]);
    }

    public function update(Request $request, int $connection, GoogleCalendarClient $client)
    {
        $this->owner($request);
        $record = $this->connection($connection);
        abort_if(in_array($record->status, ['disconnected', 'reconnect_required']), 422, 'Reconnect this Google account first.');
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'calendars' => ['required', 'array', 'min:1', 'max:10'], 'calendars.*' => ['required', 'string', 'max:1024', 'distinct'], 'template_id' => ['nullable', Rule::exists('event_templates', 'id')->where('source', 'eventnest')->whereNull('deleted_at')], 'field_mapping' => ['nullable', 'array'], 'field_mapping.*' => ['nullable', Rule::in(['title', 'description', 'starts_at', 'ends_at', 'timezone', 'location', 'status'])]]);
        $template = ! empty($data['template_id']) ? EventTemplate::findOrFail($data['template_id']) : null;
        $keys = FieldTemplate::whereIn('id', $template?->fields ?? [])->pluck('field_id')->all();
        if (array_diff(array_keys($data['field_mapping'] ?? []), $keys)) {
            throw ValidationException::withMessages(['field_mapping' => 'Map only fields belonging to the selected template.']);
        }
        try {
            $available = collect($client->calendars($record))->keyBy('id');
        } catch (GoogleCalendarException $error) {
            return back()->with('error', $error->getMessage());
        }
        if (array_diff($data['calendars'], $available->keys()->all())) {
            throw ValidationException::withMessages(['calendars' => 'Select calendars you can currently read.']);
        }
        DB::transaction(function () use ($record, $data, $available) {
            $active = ProviderConnection::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($active->status, ['disconnected', 'reconnect_required']), 422, 'Reconnect this account first.');
            $active->update(['name' => $data['name'], 'template_id' => $data['template_id'] ?? null, 'field_mapping' => $data['field_mapping'] ?? [], 'settings' => array_replace($active->settings ?? [], ['revision' => (string) Str::uuid(), 'calendars' => $available->only($data['calendars'])->values()->all()]), 'status' => 'queued', 'last_error' => null]);
        });
        SyncGoogleCalendarEvents::dispatch($request->user()->current_team_id, $record->id);

        return to_route('integrations.index')->with('success', 'Google calendar import queued. Events arrive as drafts.');
    }
}
