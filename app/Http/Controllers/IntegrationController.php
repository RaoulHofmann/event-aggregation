<?php

namespace App\Http\Controllers;

use App\Integrations\GoogleCalendarClient;
use App\Jobs\SyncGoogleCalendarEvents;
use App\Jobs\SyncHumanitixEvents;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\ProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Integrations/Index', ['googleConfigured' => app(GoogleCalendarClient::class)->configured(), 'connections' => ProviderConnection::orderBy('id')->get(), 'eventTemplates' => EventTemplate::where('source', 'eventnest')->orderBy('name')->get(), 'fieldTemplates' => FieldTemplate::orderBy('label')->get(), 'canManage' => $request->user()->ownsTeam($request->user()->currentTeam)]);
    }

    private function owner(Request $request): void
    {
        abort_unless($request->user()->ownsTeam($request->user()->currentTeam), 403);
    }

    private function credentials(Request $request, bool $updating = false): array
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'api_key' => [$updating ? 'nullable' : 'required', 'string', 'max:2048'], 'template_id' => ['nullable', Rule::exists('event_templates', 'id')->where('source', 'eventnest')->whereNull('deleted_at')], 'field_mapping' => ['nullable', 'array'], 'field_mapping.*' => ['nullable', Rule::in(['title', 'description', 'starts_at', 'ends_at', 'timezone', 'location', 'status'])]]);
        $template = ! empty($data['template_id']) ? EventTemplate::findOrFail($data['template_id']) : null;
        $keys = FieldTemplate::whereIn('id', $template?->fields ?? [])->pluck('field_id')->all();
        if (array_diff(array_keys($data['field_mapping'] ?? []), $keys)) {
            throw ValidationException::withMessages(['field_mapping' => 'Map only fields belonging to the selected template.']);
        }

        return $data;
    }

    public function store(Request $request)
    {
        $this->owner($request);
        $v = $this->credentials($request);
        $record = ProviderConnection::create(['provider' => 'humanitix', 'name' => $v['name'], 'credentials' => ['api_key' => $v['api_key']], 'status' => 'queued', 'template_id' => $v['template_id'] ?? null, 'field_mapping' => $v['field_mapping'] ?? []]);
        SyncHumanitixEvents::dispatch($request->user()->current_team_id, $record->id);

        return to_route('integrations.index')->with('success', 'Connection saved. First sync queued.');
    }

    public function update(Request $request, int $connection)
    {
        $this->owner($request);
        $record = ProviderConnection::findOrFail($connection);
        abort_unless($record->provider === 'humanitix', 404);
        $v = $this->credentials($request, true);
        abort_if($record->status === 'disconnected' && empty($v['api_key']), 422, 'Enter an API key to reconnect.');
        $record->update(['name' => $v['name'], 'credentials' => ! empty($v['api_key']) ? ['api_key' => $v['api_key']] : $record->credentials, 'template_id' => $v['template_id'] ?? null, 'field_mapping' => $v['field_mapping'] ?? [], 'status' => 'queued', 'last_error' => null]);
        SyncHumanitixEvents::dispatch($request->user()->current_team_id, $record->id);

        return to_route('integrations.index')->with('success', 'Connection updated. Sync queued.');
    }

    public function sync(Request $request, int $connection)
    {
        $this->owner($request);
        $record = DB::transaction(function () use ($connection) {
            $record = ProviderConnection::whereKey($connection)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($record->provider, ['humanitix', 'google_calendar']), 404);
            abort_if(in_array($record->status, ['disconnected', 'reconnect_required', 'needs_configuration']), 422, 'Configure or reconnect this account first.');
            $record->update(['status' => 'queued', 'last_error' => null]);

            return $record;
        });
        if ($record->provider === 'google_calendar') {
            SyncGoogleCalendarEvents::dispatch($request->user()->current_team_id, $record->id);
        } else {
            SyncHumanitixEvents::dispatch($request->user()->current_team_id, $record->id);
        }

        return back()->with('success', 'Sync queued.');
    }

    public function destroy(Request $request, int $connection)
    {
        $this->owner($request);
        DB::transaction(function () use ($connection) {
            $record = ProviderConnection::whereKey($connection)->lockForUpdate()->firstOrFail();
            $updates = ['status' => 'disconnected', 'credentials' => [], 'last_error' => null];
            if ($record->provider === 'google_calendar') {
                $updates['settings'] = array_replace($record->settings ?? [], ['revision' => (string) Str::uuid()]);
            }
            $record->update($updates);
        });

        return back()->with('success', 'Disconnected. Imported events have been kept.');
    }
}
