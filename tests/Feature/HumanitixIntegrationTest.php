<?php

namespace Tests\Feature;

use App\Integrations\SyncHumanitix;
use App\Jobs\SyncHumanitixEvents;
use App\Models\Event;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\ProvisionTeam;
use App\Traits\SchemaManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HumanitixIntegrationTest extends TestCase
{
    use RefreshDatabase,SchemaManagement;

    private function connection(): array
    {
        $user = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($user, 'Humanitix workspace', true);
        $this->setSchema($team->schema_name);
        $c = ProviderConnection::create(['provider' => 'humanitix', 'name' => 'Test account', 'credentials' => ['api_key' => 'test-key'], 'status' => 'queued']);

        return [$user, $team, $c];
    }

    private function remote(string $name = 'Humanitix workshop'): array
    {
        return ['_id' => 'external-1', 'name' => $name, 'published' => true, 'description' => '<p>Workshop details</p>', 'startDate' => '2026-12-01T10:00:00+08:00', 'endDate' => '2026-12-01T11:00:00+08:00', 'timezone' => 'Australia/Perth', 'eventLocation' => ['venueName' => 'Perth venue'], 'url' => 'https://events.humanitix.com/workshop'];
    }

    private function page(array $events, int $page = 1, int $total = 1): array
    {
        return ['events' => $events, 'page' => $page, 'pageSize' => 1, 'total' => $total];
    }

    public function test_credentials_are_encrypted_hidden_and_excluded_from_jobs(): void
    {
        [$user,$team,$c] = $this->connection();
        $this->assertStringNotContainsString('test-key', DB::table('provider_connections')->value('credentials'));
        $this->assertArrayNotHasKey('credentials', $c->toArray());
        $this->assertStringNotContainsString('test-key', serialize(new SyncHumanitixEvents($team->id, $c->id)));
        $this->actingAs($user)->get('/integrations')->assertOk()->assertDontSee('test-key');
    }

    public function test_imports_paginate_and_update_without_duplicates(): void
    {
        [$user,$team,$c] = $this->connection();
        $second = $this->remote('Second workshop');
        $second['_id'] = 'external-2';
        Http::preventStrayRequests();
        Http::fake(['api.humanitix.com/*' => Http::sequence()->push($this->page([$this->remote()], 1, 2))->push($this->page([$second], 2, 2))->push($this->page([$this->remote('Updated workshop')]))]);
        (new SyncHumanitixEvents($team->id, $c->id))->handle(app(SyncHumanitix::class));
        $this->setSchema($team->schema_name);
        $this->assertSame(2, Event::count());
        $this->assertSame('connected', $c->fresh()->status);
        $this->assertSame('Workshop details', Event::first()->description);
        (new SyncHumanitixEvents($team->id, $c->id))->handle(app(SyncHumanitix::class));
        $this->setSchema($team->schema_name);
        $this->assertSame(2, Event::count());
        Http::assertSent(fn ($r) => $r->hasHeader('x-api-key', 'test-key') && $r['page'] === 2);
        $this->actingAs($user)->getJson('/api/v1/events?source=humanitix&per_page=1')->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.source.provider', 'humanitix');
        $this->getJson('/api/events?template_id=1')->assertOk()->assertJsonFragment(['title' => 'Updated workshop']);
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
    }

    public function test_rejected_requests_produce_safe_errors_and_reset_schema(): void
    {
        [,$team,$c] = $this->connection();
        Http::fake(['api.humanitix.com/*' => Http::response(['secret' => 'must-not-be-stored'], 401)]);
        try {
            (new SyncHumanitixEvents($team->id, $c->id))->handle(app(SyncHumanitix::class));
            $this->fail('Sync should fail.');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('test-key', $e->getMessage());
        }
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
        $this->setSchema($team->schema_name);
        $this->assertSame('error', $c->fresh()->status);
        $this->assertNull($c->fresh()->last_synced_at);
        $this->assertStringNotContainsString('must-not-be-stored', $c->fresh()->last_error);
        $this->assertSame(0, Event::count());
    }

    public function test_connection_ids_are_isolated_and_disconnect_clears_keys(): void
    {
        [$a] = $this->connection();
        $this->connection();
        $foreign = ProviderConnection::create(['provider' => 'humanitix', 'name' => 'Other', 'credentials' => ['api_key' => 'other-key']]);
        Queue::fake();
        $this->actingAs($a)->post('/integrations/'.$foreign->id.'/sync')->assertNotFound();
        $this->delete('/integrations/1')->assertRedirect();
        $this->setSchema($a->currentTeam->schema_name);
        $record = ProviderConnection::findOrFail(1);
        $this->assertSame('disconnected', $record->status);
        $this->assertSame([], $record->credentials);
        Queue::assertNothingPushed();
    }

    public function test_imported_events_are_read_only_and_hidden_records_stay_hidden(): void
    {
        [$user,$team,$c] = $this->connection();
        Http::fake(['api.humanitix.com/*' => Http::response($this->page([$this->remote()]))]);
        (new SyncHumanitixEvents($team->id, $c->id))->handle(app(SyncHumanitix::class));
        $this->actingAs($user)->put('/events/1', ['template_id' => 1, 'event_data' => ['title' => 'Changed']])->assertForbidden();
        $this->delete('/events/1')->assertRedirect();
        (new SyncHumanitixEvents($team->id, $c->id))->handle(app(SyncHumanitix::class));
        $this->setSchema($team->schema_name);
        $this->assertSame(0, Event::count());
        $this->assertSame(1, Event::withTrashed()->count());
    }

    public function test_malformed_pages_do_not_import_events(): void
    {
        [,$team,$c] = $this->connection();
        Http::fake(['api.humanitix.com/*' => Http::response(['events' => [['_id' => 'bad']], 'page' => 1, 'pageSize' => 100, 'total' => 1])]);
        try {
            (new SyncHumanitixEvents($team->id, $c->id))->handle(app(SyncHumanitix::class));
            $this->fail('Invalid response must fail.');
        } catch (\RuntimeException) {
            $this->setSchema($team->schema_name);
            $this->assertSame(0, Event::count());
        }
    }

    public function test_connecting_queues_only_identifiers_and_never_flashes_keys(): void
    {
        [$user,$team] = $this->connection();
        Queue::fake();
        $this->actingAs($user)->post('/integrations', ['name' => 'My account', 'api_key' => 'new-test-key'])->assertRedirect(route('integrations.index'));
        Queue::assertPushed(SyncHumanitixEvents::class, fn ($job) => $job->teamId === $team->id && ! str_contains(serialize($job), 'new-test-key'));
        $this->post('/integrations', ['name' => '', 'api_key' => 'never-flash-key'])->assertSessionHasErrors('name')->assertSessionMissing('_old_input.api_key');
    }

    public function test_only_the_owner_can_manage_connections(): void
    {
        [,$team] = $this->connection();
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'editor']);
        $member->forceFill(['current_team_id' => $team->id])->save();
        Queue::fake();
        $this->actingAs($member)->post('/integrations/1/sync')->assertForbidden();
        $this->delete('/integrations/1')->assertForbidden();
        $this->post('/integrations', ['name' => 'Unauthorized', 'api_key' => 'key'])->assertForbidden();
        Queue::assertNothingPushed();
    }

    public function test_exhausted_jobs_mark_error_without_exposing_exception_details(): void
    {
        [,$team,$c] = $this->connection();
        $c->update(['status' => 'syncing']);
        (new SyncHumanitixEvents($team->id, $c->id))->failed(new \RuntimeException('sensitive-response-test-key'));
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
        $this->setSchema($team->schema_name);
        $this->assertSame('error',$c->fresh()->status);
        $this->assertStringNotContainsString('sensitive-response-test-key',$c->fresh()->last_error);
    }
}
