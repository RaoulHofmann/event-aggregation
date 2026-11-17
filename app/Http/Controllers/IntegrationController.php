<?php

namespace App\Http\Controllers;

use App\Jobs\SyncHumanitixEvents;
use App\Models\ProviderConnection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Integrations/Index', ['connections' => ProviderConnection::orderBy('id')->get(), 'canManage' => $request->user()->ownsTeam($request->user()->currentTeam)]);
    }

    private function owner(Request $request): void
    {
        abort_unless($request->user()->ownsTeam($request->user()->currentTeam), 403);
    }

    private function credentials(Request $request): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:255'], 'api_key' => ['required', 'string', 'max:2048']]);
    }

    public function store(Request $request)
    {
        $this->owner($request);
        $v = $this->credentials($request);
        $record = ProviderConnection::create(['provider' => 'humanitix', 'name' => $v['name'], 'credentials' => ['api_key' => $v['api_key']], 'status' => 'queued']);
        SyncHumanitixEvents::dispatch($request->user()->current_team_id, $record->id);

        return to_route('integrations.index')->with('success', 'Connection saved. First sync queued.');
    }

    public function update(Request $request, int $connection)
    {
        $this->owner($request);
        $record = ProviderConnection::findOrFail($connection);
        $v = $this->credentials($request);
        $record->update(['name' => $v['name'], 'credentials' => ['api_key' => $v['api_key']], 'status' => 'queued', 'last_error' => null]);
        SyncHumanitixEvents::dispatch($request->user()->current_team_id, $record->id);

        return to_route('integrations.index')->with('success', 'Connection updated. Sync queued.');
    }

    public function sync(Request $request, int $connection)
    {
        $this->owner($request);
        $record = ProviderConnection::findOrFail($connection);
        abort_if($record->status === 'disconnected', 422);
        $record->update(['status' => 'queued', 'last_error' => null]);
        SyncHumanitixEvents::dispatch($request->user()->current_team_id, $record->id);

        return back()->with('success', 'Sync queued.');
    }

    public function destroy(Request $request, int $connection)
    {
        $this->owner($request);
        ProviderConnection::findOrFail($connection)->update(['status' => 'disconnected', 'credentials' => [], 'last_error' => null]);

        return back()->with('success','Disconnected. Imported events have been kept.');
    }
}
