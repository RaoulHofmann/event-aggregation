<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProvisionTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlatformAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_platform_admin' => true])->save();

        return $user;
    }

    public function test_pending_accounts_cannot_access_workspace_jetstream_or_api_routes(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['account_status' => User::PENDING])->save();
        $token = $user->createToken('Approval test', ['read'])->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/events')->assertForbidden();
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('account.status'));
        $this->get('/user/api-tokens')->assertRedirect(route('account.status'));
        $this->post('/teams', ['name' => 'Early workspace'])->assertRedirect(route('account.status'));
        $this->get('/account/status')->assertInertia(fn (Assert $page) => $page->component('Auth/AccountStatus')->where('status', 'pending'));
        $this->assertDatabaseCount('teams', 0);
        $this->post('/logout')->assertRedirect();
        $this->assertGuest('web');
    }

    public function test_only_platform_admins_can_open_the_dashboard_or_manage_accounts(): void
    {
        $owner = User::factory()->create();
        $target = User::factory()->create();
        $this->get('/admin')->assertRedirect(route('login'));
        $this->actingAs($owner)->get('/admin')->assertForbidden();
        $this->patch(route('admin.users.update', $target), ['action' => 'suspend'])->assertForbidden();
        $this->assertTrue($target->refresh()->isActive());

        $admin = $this->admin();
        $target->forceFill(['account_status' => User::PENDING])->save();
        $this->actingAs($admin)->get('/admin')->assertInertia(fn (Assert $page) => $page->component('Admin/Index')
            ->where('stats.pending', 1)->where('users.data.0.id', $target->id)->missing('users.data.0.password'));
        $this->patch(route('admin.users.update', $admin), ['action' => 'suspend'])->assertForbidden();
        $this->patch(route('admin.users.update', $target), ['action' => 'reject'])->assertRedirect();
        $this->assertSame(User::REJECTED, $target->refresh()->account_status);
        $this->assertDatabaseHas('admin_actions', ['actor_id' => $admin->id, 'action' => 'reject', 'target_id' => $target->id]);
    }

    public function test_admin_can_suspend_users_and_pause_or_rename_workspaces(): void
    {
        $owner = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'Before');
        $admin = $this->admin();
        $owner->createToken('Old key', ['read']);
        $this->actingAs($admin)->patch(route('admin.teams.update', $team), ['action' => 'rename', 'name' => 'Renamed'])->assertRedirect();
        $this->assertSame('Renamed', $team->refresh()->name);
        $this->patch(route('admin.teams.update', $team), ['action' => 'pause'])->assertRedirect();
        $this->assertNotNull($team->refresh()->suspended_at);
        $this->actingAs($owner)->get('/events')->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.teams.update', $team), ['action' => 'resume'])->assertRedirect();
        $this->patch(route('admin.users.update', $owner), ['action' => 'suspend'])->assertRedirect();
        $this->assertSame(0, $owner->tokens()->count());
        $this->actingAs($owner->refresh())->get('/dashboard')->assertRedirect(route('account.status'));
        $this->actingAs($admin)->patch(route('admin.users.update', $owner), ['action' => 'restore'])->assertRedirect();
        $this->actingAs($owner->refresh())->get('/events')->assertOk();
    }

    public function test_first_admin_bootstrap_is_required_on_empty_install_and_idempotent(): void
    {
        config(['eventjunction.bootstrap_admin_email' => null, 'eventjunction.bootstrap_admin_password' => null]);
        $this->artisan('eventjunction:bootstrap-admin')->assertFailed();

        config(['eventjunction.bootstrap_admin_email' => 'admin@example.test', 'eventjunction.bootstrap_admin_password' => 'temporary-test-credential']);
        $this->artisan('eventjunction:bootstrap-admin')->assertSuccessful();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($admin->isPlatformAdmin());
        $this->assertTrue(Hash::check('temporary-test-credential', $admin->password));
        $this->assertTrue($admin->currentTeam->personal_team);
        config(['eventjunction.bootstrap_admin_password' => null]);
        $this->artisan('eventjunction:bootstrap-admin')->assertSuccessful();
        $this->assertSame($admin->password, $admin->refresh()->password);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('teams', 1);
    }
}
