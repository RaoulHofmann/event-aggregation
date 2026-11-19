<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\ProvisionTeam;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([User::ACTIVE, User::PENDING, User::REJECTED, User::SUSPENDED])],
            'team_search' => ['nullable', 'string', 'max:100'],
        ]);

        $now = now();
        $counts = User::query()->selectRaw('account_status, count(*) as total')->groupBy('account_status')->pluck('total', 'account_status');

        return Inertia::render('Admin/Index', [
            'stats' => [
                'pending' => (int) ($counts[User::PENDING] ?? 0),
                'active' => (int) ($counts[User::ACTIVE] ?? 0),
                'new_registrations' => User::where('created_at', '>=', $now->copy()->subDays(7))->count(),
                'teams' => Team::count(),
                'paused_teams' => Team::whereNotNull('suspended_at')->count(),
                'queued_jobs' => DB::table('jobs')->whereNull('reserved_at')->count(),
                'failed_jobs' => DB::table('failed_jobs')->where('failed_at', '>=', $now->copy()->subDay())->count(),
                'last_sync_scan' => Cache::get('eventjunction:integration-scheduler:last-completed'),
            ],
            'filters' => $filters,
            'users' => User::query()
                ->select('id', 'name', 'email', 'account_status', 'is_platform_admin', 'created_at')
                ->withCount('ownedTeams')
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('account_status', $status))
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->whereLike('name', '%'.$search.'%')->orWhereLike('email', '%'.$search.'%')))
                ->orderByRaw("CASE WHEN account_status = 'pending' THEN 0 ELSE 1 END")
                ->latest('id')->paginate(12, pageName: 'users_page')->withQueryString()
                ->through(fn (User $user) => $user->only('id', 'name', 'email', 'account_status', 'is_platform_admin', 'created_at', 'owned_teams_count')),
            'teams' => $this->workspaces($filters['team_search'] ?? null),
            'activity' => DB::table('admin_actions')
                ->leftJoin('users as actors', 'actors.id', '=', 'admin_actions.actor_id')
                ->latest('admin_actions.id')->limit(12)->get(['admin_actions.id', 'action', 'target_id', 'target_type', 'admin_actions.created_at', 'actors.name as actor']),
            'tickets' => SupportTicket::with(['requester:id,name,email', 'workspace:id,name'])
                ->latest('id')->paginate(10, pageName: 'tickets_page')->withQueryString(),
        ]);
    }

    private function workspaces(?string $search): LengthAwarePaginator
    {
        $teams = Team::query()
            ->select('id', 'name', 'user_id', 'personal_team', 'suspended_at', 'created_at', 'schema_name')
            ->with('owner:id,name,is_platform_admin')->withCount('users')
            ->when($search, fn ($query, $search) => $query->whereLike('name', '%'.$search.'%'))
            ->latest('id')->paginate(12, pageName: 'teams_page')->withQueryString();

        $tablesBySchema = DB::table('information_schema.tables')
            ->whereIn('table_schema', $teams->getCollection()->pluck('schema_name')->unique()->all())
            ->whereNotIn('table_schema', ['public', 'information_schema'])
            ->whereIn('table_name', ['events', 'provider_connections'])
            ->get(['table_schema', 'table_name'])->groupBy('table_schema');

        return $teams->through(function (Team $team) use ($tablesBySchema) {
            $schema = $team->schema_name;
            $tables = $tablesBySchema->get($schema, collect())->pluck('table_name');
            $qualifiedSchema = '"'.str_replace('"', '""', $schema).'"';
            $counts = DB::query();
            if ($tables->contains('events')) {
                $counts->selectSub(DB::query()->fromRaw($qualifiedSchema.'.events')->selectRaw('count(*)')->whereNull('deleted_at'), 'events_count');
            }
            if ($tables->contains('provider_connections')) {
                $counts->selectSub(
                    DB::query()->fromRaw($qualifiedSchema.'.provider_connections')->selectRaw('count(*)')
                        ->where('provider', '!=', 'csv')->where('status', '!=', 'disconnected'),
                    'integrations_count',
                );
            }
            $usage = $tables->isEmpty() ? null : $counts->first();

            return $team->makeHidden('schema_name')->toArray() + [
                'events_count' => isset($usage->events_count) ? (int) $usage->events_count : null,
                'integrations_count' => isset($usage->integrations_count) ? (int) $usage->integrations_count : null,
            ];
        });
    }

    public function updateUser(Request $request, User $user, ProvisionTeam $provisioner): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', Rule::in(['approve', 'reject', 'suspend', 'restore'])]])['action'];

        DB::transaction(function () use ($request, $user, $action, $provisioner) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_if($user->is_platform_admin, 403, 'Platform administrators cannot be modified here.');
            $allowed = match ($action) {
                'approve' => [User::PENDING, User::REJECTED],
                'reject' => [User::PENDING],
                'suspend' => [User::ACTIVE],
                'restore' => [User::SUSPENDED],
            };
            abort_unless(in_array($user->account_status, $allowed, true), 409, 'The account status has already changed.');
            if ($action === 'approve' && ! $user->ownedTeams()->exists()) {
                $provisioner->create($user, str($user->name)->limit(220, '')->toString()."'s workspace", true);
            }
            $user->forceFill(['account_status' => match ($action) {
                'approve', 'restore' => User::ACTIVE,
                'reject' => User::REJECTED,
                'suspend' => User::SUSPENDED,
            }])->save();
            if ($action === 'suspend') {
                $user->tokens()->delete();
            }
            $this->record($request, $action, 'user', $user->id);
        });

        return back()->with('success', match ($action) {
            'approve' => 'Registration approved.',
            'reject' => 'Registration declined.',
            'suspend' => 'User suspended and their API tokens revoked.',
            'restore' => 'User access restored.',
        });
    }

    public function updateTeam(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['rename', 'pause', 'resume'])],
            'name' => ['required_if:action,rename', 'nullable', 'string', 'max:255'],
        ]);
        DB::transaction(function () use ($request, $team, $data) {
            $team = Team::query()->lockForUpdate()->findOrFail($team->id);
            $action = $data['action'];
            if ($action === 'pause') {
                abort_if($team->owner?->is_platform_admin, 403, 'Administrator-owned workspaces cannot be paused.');
                abort_if($team->suspended_at, 409, 'This workspace is already paused.');
                $team->forceFill(['suspended_at' => now()])->save();
            } elseif ($action === 'resume') {
                abort_unless($team->suspended_at, 409, 'This workspace is already active.');
                $team->forceFill(['suspended_at' => null])->save();
            } else {
                $team->update(['name' => $data['name']]);
            }
            $this->record($request, $action, 'team', $team->id);
        });

        return back()->with('success', 'Workspace updated.');
    }

    private function record(Request $request, string $action, string $type, int $id): void
    {
        DB::table('admin_actions')->insert([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'target_type' => $type,
            'target_id' => $id,
            'created_at' => now(),
        ]);
    }
}
