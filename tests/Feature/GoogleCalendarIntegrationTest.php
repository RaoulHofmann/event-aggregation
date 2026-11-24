<?php

namespace Tests\Feature;

use App\Integrations\GoogleCalendarClient;
use App\Integrations\GoogleCalendarException;
use App\Integrations\SyncGoogleCalendar;
use App\Jobs\SyncGoogleCalendarEvents;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\ProvisionTeam;
use App\Traits\SchemaManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GoogleCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase, SchemaManagement;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google_calendar.client_id' => 'fixture-client', 'services.google_calendar.client_secret' => 'fixture-secret', 'services.google_calendar.redirect_uri' => 'https://event-aggregator.fly.dev/integrations/google/callback']);
        Http::preventStrayRequests();
    }

    private function connection(): array
    {
        $user = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($user, 'Google workspace', true);
        $this->setSchema($team->schema_name);
        $connection = ProviderConnection::create(['provider' => 'google_calendar', 'name' => 'Google events', 'credentials' => ['access_token' => 'fixture-access', 'refresh_token' => 'fixture-refresh', 'expires_at' => now()->addHour()->timestamp], 'settings' => ['revision' => 'fixture-revision', 'account_id' => 'primary@example.com', 'calendars' => [['id' => 'primary@example.com', 'name' => 'Programme', 'timezone' => 'Australia/Perth']]], 'status' => 'connected']);

        return [$user, $team, $connection];
    }

    private function calendars(): array
    {
        return ['items' => [['id' => 'primary@example.com', 'summary' => 'Programme', 'timeZone' => 'Australia/Perth', 'primary' => true, 'accessRole' => 'owner'], ['id' => 'other@example.com', 'summary' => 'Community', 'timeZone' => 'Europe/London', 'accessRole' => 'reader']]];
    }

    private function remote(string $title = 'Garden workshop', string $id = 'event-1'): array
    {
        return ['id' => $id, 'summary' => $title, 'description' => '<p>Bring gloves</p>', 'location' => 'Fremantle', 'status' => 'confirmed', 'start' => ['dateTime' => '2026-12-01T10:00:00+08:00', 'timeZone' => 'Australia/Perth'], 'end' => ['dateTime' => '2026-12-01T11:00:00+08:00'], 'htmlLink' => 'https://calendar.google.com/calendar/event?eid=fixture'];
    }

    private function fakeEvents($events): void
    {
        Http::fake(['www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response($this->calendars()), 'www.googleapis.com/calendar/v3/calendars/*/events*' => $events]);
    }

    public function test_oauth_is_read_only_pkce_bound_and_single_use(): void
    {
        [$user, $team] = $this->connection();
        DB::select("SELECT set_config('search_path','public',false)");
        $this->actingAs($user)->get('/integrations/google/connect')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Integrations/GoogleCalendarConsent')->where('connectionId', null))
            ->assertSessionMissing('google_calendar_oauth');
        $this->postJson('/integrations/google/connect', ['consent' => false])->assertUnprocessable()
            ->assertJsonValidationErrors('consent')->assertSessionMissing('google_calendar_oauth');
        $response = $this->post('/integrations/google/connect', ['consent' => true]);
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame(implode(' ', GoogleCalendarClient::SCOPES), $query['scope']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $flow = session('google_calendar_oauth');
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', $flow['verifier'], true)), '+/', '-_'), '='), $query['code_challenge']);
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'expires_in' => 3600, 'scope' => implode(' ', GoogleCalendarClient::SCOPES)]), 'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response($this->calendars())]);
        $this->get('/integrations/google/callback?state='.urlencode($query['state']).'&code=fixture-code')->assertRedirect();
        $this->setSchema($team->schema_name);
        $record = ProviderConnection::orderByDesc('id')->firstOrFail();
        $this->assertSame('needs_configuration', $record->status);
        $this->assertSame('new-refresh', $record->credentials['refresh_token']);
        $this->assertStringNotContainsString('new-refresh', DB::table('provider_connections')->where('id', $record->id)->value('credentials'));
        $this->assertArrayNotHasKey('credentials', $record->toArray());
        $this->assertStringNotContainsString('new-refresh', serialize(new SyncGoogleCalendarEvents($team->id, $record->id)));
        $this->get('/integrations')->assertOk()->assertDontSee('new-refresh')->assertDontSee('fixture-secret');
        $this->get('/integrations/google/callback?state='.urlencode($query['state']).'&code=fixture-code')->assertForbidden();
        Http::assertSent(fn ($r) => $r->url() === 'https://oauth2.googleapis.com/token' && $r->method() === 'POST' && $r['code_verifier'] === $flow['verifier']);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/calendar/v3/') && $r->method() !== 'GET');
    }

    public function test_invalid_expired_and_cross_workspace_state_never_exchanges_tokens(): void
    {
        [$user, $team] = $this->connection();
        $flow = ['state' => 'state', 'verifier' => 'verifier', 'user_id' => $user->id, 'team_id' => $team->id, 'expires_at' => now()->addMinutes(10)->timestamp, 'connection_id' => null];
        $this->actingAs($user)->withSession(['google_calendar_oauth' => $flow])->get('/integrations/google/callback?state=wrong&code=secret-code')->assertForbidden();
        $this->withSession(['google_calendar_oauth' => array_replace($flow, ['expires_at' => now()->subMinute()->timestamp])])->get('/integrations/google/callback?state=state&code=secret-code')->assertForbidden();
        $this->withSession(['google_calendar_oauth' => array_replace($flow, ['team_id' => $team->id + 1])])->get('/integrations/google/callback?state=state&code=secret-code')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_consent_submitted_with_inertia_triggers_full_google_navigation(): void
    {
        [$user] = $this->connection();
        $response = $this->actingAs($user)->post('/integrations/google/connect', ['consent' => true], ['X-Inertia' => 'true']);

        $response->assertStatus(409)->assertSessionHas('google_calendar_oauth');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $response->headers->get('X-Inertia-Location'));
    }

    public function test_owner_only_calendar_selection_mapping_and_provider_routes(): void
    {
        [$user, $team, $connection] = $this->connection();
        $field = FieldTemplate::create(['field_id' => 'workshop_title', 'label' => 'Workshop title', 'type' => 'text']);
        $template = EventTemplate::create(['name' => 'Workshops', 'fields' => [$field->id], 'layout' => [[$field->id], []]]);
        $this->fakeEvents(Http::response(['items' => []]));
        Queue::fake();
        $this->actingAs($user)->get('/integrations/google/'.$connection->id.'/edit')->assertOk()->assertDontSee('fixture-refresh');
        $this->put('/integrations/google/'.$connection->id, ['name' => 'My calendar', 'calendars' => ['primary@example.com'], 'template_id' => $template->id, 'field_mapping' => ['workshop_title' => 'title']])->assertRedirect(route('integrations.index'));
        Queue::assertPushed(SyncGoogleCalendarEvents::class, fn ($job) => $job->teamId === $team->id && $job->connectionId === $connection->id);
        $this->put('/integrations/'.$connection->id, ['name' => 'Wrong provider'])->assertNotFound();
        $this->put('/integrations/google/'.$connection->id, ['name' => 'Bad', 'calendars' => ['foreign@example.com']])->assertSessionHasErrors('calendars');
        $this->put('/integrations/google/'.$connection->id, ['name' => 'Bad', 'calendars' => ['primary@example.com'], 'template_id' => $template->id, 'field_mapping' => ['foreign_field' => 'title']])->assertSessionHasErrors('field_mapping');
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'editor']);
        $member->forceFill(['current_team_id' => $team->id])->save();
        $this->actingAs($member)->get('/integrations/google/connect')->assertForbidden();
        $this->get('/integrations/google/'.$connection->id.'/edit')->assertForbidden();
        $this->put('/integrations/google/'.$connection->id, ['name' => 'No'])->assertForbidden();
    }

    public function test_paginated_imports_templates_local_edits_and_deletes_are_google_get_only(): void
    {
        [$user, $team, $connection] = $this->connection();
        $field = FieldTemplate::create(['field_id' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true]);
        $template = EventTemplate::create(['name' => 'Workshop', 'fields' => [$field->id], 'layout' => [[$field->id], []]]);
        $connection->update(['template_id' => $template->id, 'field_mapping' => ['heading' => 'title']]);
        $changed = $this->remote('Changed upstream');
        $changed['location'] = 'New venue';
        $this->fakeEvents(Http::sequence()->push(['items' => [$this->remote()], 'nextPageToken' => 'page-two'])->push(['items' => [$this->remote('Second', 'event-2')]])->push(['items' => [$changed, $this->remote('Second', 'event-2')]])->push(['items' => [$changed, $this->remote('Second', 'event-2')]]));
        app(SyncGoogleCalendar::class)->sync($connection);
        $this->assertSame(2, Event::count());
        $event = Event::orderBy('id')->firstOrFail();
        $this->assertSame('draft', $event->status);
        $this->assertSame('Bring gloves', $event->description);
        $this->assertSame('Garden workshop', $event->event_data['heading']);
        $this->actingAs($user)->put('/events/'.$event->id, ['title' => 'Local workshop', 'description' => 'Bring gloves', 'location' => 'Fremantle', 'starts_at' => '2026-12-01T10:00', 'ends_at' => '2026-12-01T11:00', 'timezone' => 'Australia/Perth', 'status' => 'published', 'template_id' => $template->id, 'event_data' => ['heading' => 'Local answer']])->assertRedirect();
        $this->setSchema($team->schema_name);
        app(SyncGoogleCalendar::class)->sync($connection);
        $event->refresh();
        $this->assertSame('Local workshop', $event->title);
        $this->assertSame('Local answer', $event->event_data['heading']);
        $this->assertSame('New venue', $event->location);
        $this->assertSame('published', $event->status);
        $this->assertSame('Changed upstream', $event->source_snapshot['title']);
        $this->getJson('/api/v1/events?source=google_calendar&status=published')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.source.provider', 'google_calendar');
        $this->getJson('/api/v1/occurrences?source=google_calendar&from=2026-12-01&to=2026-12-03')->assertOk();
        $this->delete('/events/'.$event->id)->assertRedirect();
        $this->setSchema($team->schema_name);
        app(SyncGoogleCalendar::class)->sync($connection);
        $this->assertSame(1, Event::count());
        $this->assertSame(2, Event::withTrashed()->count());
        Http::assertSent(fn ($r) => ($r['pageToken'] ?? null) === 'page-two');
        Http::assertNotSent(fn ($r) => $r->method() !== 'GET');
    }

    public function test_expanded_recurrence_all_day_timezone_and_calendar_id_collisions(): void
    {
        [,, $connection] = $this->connection();
        $connection->update(['settings' => array_replace($connection->settings, ['calendars' => [['id' => 'primary@example.com'], ['id' => 'other@example.com']]])]);
        $allDay = $this->remote('All day', 'shared-id');
        $allDay['start'] = ['date' => '2026-12-01'];
        $allDay['end'] = ['date' => '2026-12-02'];
        $allDay['recurringEventId'] = 'series';
        $this->fakeEvents(Http::response(['items' => [$allDay]]));
        app(SyncGoogleCalendar::class)->sync($connection);
        $events = Event::orderBy('id')->get();
        $this->assertCount(2, $events);
        $this->assertNotSame($events[0]->external_id, $events[1]->external_id);
        $this->assertTrue($events[0]->all_day);
        $this->assertSame('2026-11-30 16:00:00', $events[0]->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-01 16:00:00', $events[0]->ends_at->utc()->format('Y-m-d H:i:s'));
        $this->assertNull($events[0]->recurrence);
        $this->assertSame('series', $events[0]->source_snapshot['google_recurring_event_id']);
        Http::assertSent(fn ($r) => ($r['singleEvents'] ?? null) === 'true' && $r['showDeleted'] === 'true' && isset($r['timeMin']) && isset($r['timeMax']));
    }

    public function test_cancellation_preserves_local_publication_and_never_creates_unknown_tombstones(): void
    {
        [,, $connection] = $this->connection();
        $this->fakeEvents(Http::sequence()->push(['items' => [$this->remote()]])->push(['items' => [['id' => 'event-1', 'status' => 'cancelled'], ['id' => 'unknown', 'status' => 'cancelled']]])->push(['items' => [['id' => 'event-1', 'status' => 'cancelled']]]));
        app(SyncGoogleCalendar::class)->sync($connection);
        app(SyncGoogleCalendar::class)->sync($connection);
        $event = Event::firstOrFail();
        $this->assertSame(1, Event::count());
        $this->assertSame('cancelled', $event->status);
        $event->forceFill(['status' => 'published', 'local_overrides' => ['status']])->save();
        app(SyncGoogleCalendar::class)->sync($connection);
        $this->assertSame('published', $event->fresh()->status);
        $this->assertSame('cancelled', $event->fresh()->source_snapshot['google_status']);
    }

    public function test_missing_event_is_checked_and_permission_failure_does_not_cancel_it(): void
    {
        [,, $connection] = $this->connection();
        Http::fake(['www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response($this->calendars()), 'www.googleapis.com/calendar/v3/calendars/*/events/event-1' => Http::sequence()->push(['sensitive' => 'must-not-leak'], 403)->push([], 410), 'www.googleapis.com/calendar/v3/calendars/*/events?*' => Http::sequence()->push(['items' => [$this->remote()]])->push(['items' => []])->push(['items' => []])]);
        app(SyncGoogleCalendar::class)->sync($connection);
        try {
            app(SyncGoogleCalendar::class)->sync($connection);
            $this->fail('Permission error must fail safely');
        } catch (GoogleCalendarException $error) {
            $this->assertStringNotContainsString('must-not-leak', $error->getMessage());
        }
        $this->assertSame('draft', Event::first()->status);
        app(SyncGoogleCalendar::class)->sync($connection);
        $this->assertSame('cancelled', Event::first()->status);
    }

    public function test_expired_tokens_refresh_only_at_oauth_endpoint_and_revocation_needs_reconnect(): void
    {
        [,$team,$connection] = $this->connection();
        $connection->update(['credentials' => array_replace($connection->credentials, ['expires_at' => now()->subMinute()->timestamp])]);
        Http::fake(['oauth2.googleapis.com/token' => Http::sequence()->push(['access_token' => 'renewed-access', 'expires_in' => 3600])->push(['error' => 'invalid_grant', 'error_description' => 'sensitive-provider-error'], 400), 'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response($this->calendars()), 'www.googleapis.com/calendar/v3/calendars/*/events*' => Http::response(['items' => []])]);
        (new SyncGoogleCalendarEvents($team->id, $connection->id))->handle(app(SyncGoogleCalendar::class));
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
        $this->setSchema($team->schema_name);
        $this->assertSame('fixture-refresh', $connection->fresh()->credentials['refresh_token']);
        $this->assertSame('connected', $connection->fresh()->status);
        $connection->update(['credentials' => array_replace($connection->fresh()->credentials, ['expires_at' => now()->subMinute()->timestamp])]);
        (new SyncGoogleCalendarEvents($team->id, $connection->id))->handle(app(SyncGoogleCalendar::class));
        $this->setSchema($team->schema_name);
        $this->assertSame('reconnect_required', $connection->fresh()->status);
        $this->assertStringNotContainsString('sensitive-provider-error', $connection->fresh()->last_error);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/calendar/v3/') && $r->method() !== 'GET');
    }

    public function test_disconnect_clears_credentials_stops_jobs_and_keeps_imports(): void
    {
        [$user, $team, $connection] = $this->connection();
        $this->fakeEvents(Http::response(['items' => [$this->remote()]]));
        app(SyncGoogleCalendar::class)->sync($connection);
        $this->actingAs($user)->delete('/integrations/'.$connection->id)->assertRedirect();
        $this->setSchema($team->schema_name);
        $this->assertSame([], $connection->fresh()->credentials);
        $this->assertSame(1, Event::count());
        Http::assertSentCount(2);
        (new SyncGoogleCalendarEvents($team->id, $connection->id))->handle(app(SyncGoogleCalendar::class));
        Http::assertSentCount(2);
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
    }

    public function test_malformed_pages_and_dates_fail_safely_and_job_resets_schema(): void
    {
        [,$team,$connection] = $this->connection();
        $this->fakeEvents(Http::response(['items' => [['id' => 'bad', 'status' => 'confirmed', 'start' => ['dateTime' => 'not a date']]]]));
        try {
            (new SyncGoogleCalendarEvents($team->id, $connection->id))->handle(app(SyncGoogleCalendar::class));
            $this->fail('Malformed dates should fail');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('fixture-access', $e->getMessage());
        }
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
        $this->setSchema($team->schema_name);
        $this->assertSame('error', $connection->fresh()->status);
        $this->assertSame(0, Event::count());
        $this->assertNull($connection->fresh()->last_synced_at);
    }

    public function test_reconnect_rejects_another_google_account_without_replacing_credentials(): void
    {
        [$user, $team, $connection] = $this->connection();
        $flow = ['state' => 'state', 'verifier' => 'verifier', 'user_id' => $user->id, 'team_id' => $team->id, 'expires_at' => now()->addMinutes(10)->timestamp, 'connection_id' => $connection->id, 'revision' => $connection->settings['revision']];
        $calendars = $this->calendars();
        $calendars['items'][0]['id'] = 'different-account@example.com';
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'other-access', 'refresh_token' => 'other-refresh', 'expires_in' => 3600, 'scope' => implode(' ', GoogleCalendarClient::SCOPES)]), 'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response($calendars)]);
        $this->actingAs($user)->withSession(['google_calendar_oauth' => $flow])->get('/integrations/google/callback?state=state&code=code')->assertRedirect(route('integrations.index'))->assertSessionHas('error');
        $this->setSchema($team->schema_name);
        $this->assertSame('fixture-refresh', $connection->fresh()->credentials['refresh_token']);
        $this->assertSame('primary@example.com', $connection->fresh()->settings['account_id']);
    }

    public function test_partial_consent_and_denial_do_not_create_connections_or_expose_provider_errors(): void
    {
        [$user, $team] = $this->connection();
        $flow = ['state' => 'state', 'verifier' => 'verifier', 'user_id' => $user->id, 'team_id' => $team->id, 'expires_at' => now()->addMinutes(10)->timestamp, 'connection_id' => null];
        $this->actingAs($user)->withSession(['google_calendar_oauth' => $flow])->get('/integrations/google/callback?state=state&error=access_denied')->assertRedirect()->assertSessionHas('error');
        Http::assertNothingSent();
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'sensitive-token', 'refresh_token' => 'sensitive-refresh', 'expires_in' => 3600, 'scope' => GoogleCalendarClient::SCOPES[0]])]);
        $this->withSession(['google_calendar_oauth' => $flow])->get('/integrations/google/callback?state=state&code=sensitive-code')->assertRedirect()->assertSessionHas('error')->assertSessionMissing('_old_input.code');
        $this->setSchema($team->schema_name);
        $this->assertSame(1, ProviderConnection::count());
        $this->assertStringNotContainsString('sensitive', session('error'));
        Http::assertSentCount(1);
    }

    public function test_foreign_connection_ids_are_inaccessible_and_scheduler_skips_inactive_google(): void
    {
        [$user, $team, $connection] = $this->connection();
        [, , $other] = $this->connection();
        $foreign = ProviderConnection::create(['provider' => 'google_calendar', 'name' => 'Foreign', 'credentials' => [], 'status' => 'disconnected']);
        $this->actingAs($user)->get('/integrations/google/'.$foreign->id.'/edit')->assertNotFound();
        $this->put('/integrations/google/'.$foreign->id, [])->assertNotFound();
        $this->get('/integrations/google/connect?connection='.$foreign->id)->assertNotFound();
        Queue::fake();
        $this->setSchema($team->schema_name);
        ProviderConnection::create(['provider' => 'google_calendar', 'name' => 'Setup needed', 'credentials' => [], 'status' => 'needs_configuration']);
        $this->artisan('integrations:sync')->assertSuccessful();
        Queue::assertPushed(SyncGoogleCalendarEvents::class, 2);
        Queue::assertPushed(SyncGoogleCalendarEvents::class, fn ($job) => $job->teamId === $team->id && $job->connectionId === $connection->id);
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
        Http::assertNothingSent();
    }

    public function test_repeated_page_tokens_fail_without_inferring_cancellations(): void
    {
        [,, $connection] = $this->connection();
        $this->fakeEvents(Http::sequence()->push(['items' => [$this->remote()]])->push(['items' => [], 'nextPageToken' => 'repeated'])->push(['items' => [], 'nextPageToken' => 'repeated']));
        app(SyncGoogleCalendar::class)->sync($connection);
        try {
            app(SyncGoogleCalendar::class)->sync($connection);
            $this->fail('Repeated tokens must fail');
        } catch (GoogleCalendarException) {
            $this->assertSame('draft', Event::first()->status);
        }
        Http::assertSentCount(5);
    }

    public function test_disconnect_invalidates_pending_reconnect_callback(): void
    {
        [$user, $team, $connection] = $this->connection();
        $this->actingAs($user)->post('/integrations/google/connect', ['connection' => $connection->id, 'consent' => true])->assertRedirect();
        $flow = session('google_calendar_oauth');
        $this->delete('/integrations/'.$connection->id)->assertRedirect();
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'expires_in' => 3600, 'scope' => implode(' ', GoogleCalendarClient::SCOPES)]), 'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response($this->calendars())]);
        $this->get('/integrations/google/callback?state='.$flow['state'].'&code=code')->assertRedirect(route('integrations.index'))->assertSessionHas('error');
        $this->setSchema($team->schema_name);
        $this->assertSame([], $connection->fresh()->credentials);
        $this->assertSame('disconnected', $connection->fresh()->status);
    }
}
