<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\User;
use App\Services\ProvisionTeam;
use App\Traits\SchemaManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventNestFoundationTest extends TestCase
{
    use RefreshDatabase,SchemaManagement;

    private function workspace(string $title, int $count = 1): array
    {
        $user = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($user, 'Same display name', true);
        $this->setSchema($team->schema_name);
        $field = FieldTemplate::create(['label' => $title, 'field_id' => 'title', 'type' => 'text', 'required' => true]);
        $template = EventTemplate::create(['name' => $title, 'fields' => [$field->id], 'layout' => [[$field->id]]]);
        for ($i = 0; $i < $count; $i++) {
            $event = Event::create(['template_id' => $template->id, 'event_data' => ['title' => $title]]);
        }
        DB::select("SELECT set_config('search_path','public',false)");

        return [$user, $team, $event];
    }

    public function test_same_display_names_have_unique_schemas(): void
    {
        [,$a] = $this->workspace('First');
        [,$b] = $this->workspace('Second');
        $this->assertNotSame($a->schema_name, $b->schema_name);
        $this->assertNotNull(DB::selectOne('SELECT to_regclass(?) AS name', [$a->schema_name.'.events'])->name);
    }

    public function test_native_events_can_be_created_and_read_in_legacy_and_versioned_apis(): void
    {
        [$user] = $this->workspace('First');
        $this->actingAs($user)->post('/events', ['template_id' => 1, 'event_data' => ['title' => 'Native workshop'], 'title' => 'Perth workshop', 'starts_at' => '2026-12-01T10:00', 'ends_at' => '2026-12-01T11:00', 'timezone' => 'Australia/Perth', 'status' => 'published'])->assertRedirect(route('events.index'));
        $this->getJson('/api/events?template_name=First')->assertOk()->assertJsonPath('total', 2)->assertJsonFragment(['title' => 'Perth workshop']);
        $this->getJson('/api/v1/events?source=eventnest&status=published&per_page=1')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.starts_at', '2026-12-01T02:00:00+00:00')->assertJsonPath('data.0.timezone', 'Australia/Perth');
        $this->getJson('/api/v1/events?per_page=1000')->assertUnprocessable();
    }

    public function test_required_custom_fields_are_validated(): void
    {
        [$user] = $this->workspace('First');
        $this->actingAs($user)->postJson('/events', ['template_id' => 1, 'event_data' => ['title' => '']])->assertUnprocessable()->assertJsonValidationErrors('event_data.title');
    }

    public function test_reads_are_isolated_and_search_path_is_reset(): void
    {
        [$user] = $this->workspace('First');
        $this->workspace('Second');
        $this->actingAs($user)->getJson('/api/events?template_name=First')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.event_data.title', 'First');
        $this->getJson('/api/events?template_name=Second')->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/field-templates')->assertOk()->assertJsonCount(1)->assertJsonPath('0.label', 'First');
        $this->getJson('/api/event-templates')->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'First');
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
    }

    public function test_foreign_event_ids_cannot_be_written_or_deleted(): void
    {
        [$user] = $this->workspace('First');
        [,, $event] = $this->workspace('Second', 2);
        $this->actingAs($user)->put('/events/'.$event->id, ['template_id' => 1, 'event_data' => ['title' => 'Changed']])->assertNotFound();
        $this->delete('/events/'.$event->id)->assertNotFound();
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
    }

    public function test_foreign_fields_and_templates_cannot_be_modified(): void
    {
        [$user] = $this->workspace('First');
        [,$other] = $this->workspace('Second');
        $this->setSchema($other->schema_name);
        $field = FieldTemplate::create(['label' => 'Other', 'field_id' => 'other', 'type' => 'text']);
        $template = EventTemplate::create(['name' => 'Other', 'fields' => [$field->id], 'layout' => [[$field->id]]]);
        DB::select("SELECT set_config('search_path','public',false)");
        $this->actingAs($user)->put('/field-templates/'.$field->id, ['label' => 'Changed', 'type' => 'text'])->assertNotFound();
        $this->delete('/field-templates/'.$field->id)->assertNotFound();
        $this->put('/event-templates/'.$template->id, ['name' => 'Changed', 'fields' => [1], 'layout' => [[1]]])->assertNotFound();
        $this->delete('/event-templates/'.$template->id)->assertNotFound();
    }

    public function test_stale_membership_and_reserved_schemas_are_rejected(): void
    {
        [$user] = $this->workspace('First');
        [,$other] = $this->workspace('Second');
        $user->forceFill(['current_team_id' => $other->id])->save();
        $user->unsetRelation('currentTeam');
        $this->actingAs($user)->getJson('/api/events')->assertForbidden();
        $this->assertFalse($this->isTenantSchema('public'));
    }

    public function test_api_tokens_require_read_and_use_the_owners_workspace(): void
    {
        [$a] = $this->workspace('First');
        [$b] = $this->workspace('Second');
        $read = $a->createToken('Read', ['read'])->plainTextToken;
        $denied = $b->createToken('No read', ['update'])->plainTextToken;
        $this->withToken($read)->getJson('/api/events?template_id=1')->assertOk()->assertJsonPath('data.0.event_data.title', 'First');
        $this->app['auth']->forgetGuards();
        $this->withToken($denied)->getJson('/api/events')->assertForbidden();
    }

    public function test_registration_approval_provisions_a_complete_personal_workspace(): void
    {
        $this->post('/register', ['name' => 'New User', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'terms' => true])->assertRedirect(route('account.status'));
        $this->assertAuthenticated();
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertNull($user->current_team_id);
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $this->actingAs($admin)->patch(route('admin.users.update', $user), ['action' => 'approve'])->assertRedirect();
        $this->assertSame(User::ACTIVE, $user->refresh()->account_status);
        $team = $user->currentTeam;
        $this->assertTrue($team->personal_team);
        $this->assertMatchesRegularExpression('/^team_[a-f0-9]{32}$/', $team->schema_name);
        $this->assertNotNull(DB::selectOne('SELECT to_regclass(?) AS name', [$team->schema_name.'.events'])->name);
    }

    public function test_approval_rolls_back_when_schema_migration_fails(): void
    {
        $pending = User::factory()->create();
        $pending->forceFill(['account_status' => User::PENDING])->save();
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $schemas = DB::table('information_schema.schemata')->where('schema_name', 'like', 'team_%')->pluck('schema_name')->sort()->values()->all();
        $this->app->instance(ProvisionTeam::class, new class extends ProvisionTeam
        {
            public function runMigrations(string $schemaName): void
            {
                throw new \RuntimeException('Provisioning unavailable');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($admin)->patch(route('admin.users.update', $pending), ['action' => 'approve']);
            $this->fail('Incomplete workspace must not be created.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Provisioning unavailable', $e->getMessage());
        }
        $this->assertSame(User::PENDING, $pending->refresh()->account_status);
        $this->assertNull($pending->current_team_id);
        $this->assertDatabaseCount('admin_actions', 0);
        $this->assertDatabaseCount('teams', 0);
        $this->assertSame($schemas, DB::table('information_schema.schemata')->where('schema_name','like','team_%')->pluck('schema_name')->sort()->values()->all());
    }
}
