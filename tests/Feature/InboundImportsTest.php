<?php

namespace Tests\Feature;

use App\Integrations\Inbound\IcalendarEvents;
use App\Integrations\Inbound\ImportException;
use App\Integrations\Inbound\ImportWriter;
use App\Integrations\Inbound\PublicFeedClient;
use App\Integrations\Inbound\WordPressEvents;
use App\Jobs\SyncInboundEvents;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\ProvisionTeam;
use App\Traits\SchemaManagement;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InboundImportsTest extends TestCase
{
    use RefreshDatabase, SchemaManagement;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->travelTo(CarbonImmutable::parse('2026-11-18T00:00:00Z'));
    }

    private function workspace(): array
    {
        $owner = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'Example workspace', true);

        return [$owner, $team];
    }

    private function feed(): string
    {
        return implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//EventJunction//Fixture//EN',
            'BEGIN:VEVENT', 'UID:workshop@example.test', 'DTSTAMP:20261101T000000Z',
            'DTSTART;TZID=Australia/Perth:20261120T100000', 'DTEND;TZID=Australia/Perth:20261120T110000',
            'SUMMARY:Garden workshop', 'RRULE:FREQ=WEEKLY;COUNT=3', 'EXDATE;TZID=Australia/Perth:20261204T100000', 'END:VEVENT',
            'BEGIN:VEVENT', 'UID:workshop@example.test', 'DTSTAMP:20261101T000000Z',
            'RECURRENCE-ID;TZID=Australia/Perth:20261127T100000', 'DTSTART;TZID=Australia/Perth:20261127T120000',
            'DTEND;TZID=Australia/Perth:20261127T130000', 'SUMMARY:Rescheduled workshop', 'END:VEVENT',
            'BEGIN:VEVENT', 'UID:all-day@example.test', 'DTSTAMP:20261101T000000Z',
            'DTSTART;VALUE=DATE:20261122', 'DTEND;VALUE=DATE:20261123', 'SUMMARY:Open day', 'END:VEVENT',
            'END:VCALENDAR', '',
        ]);
    }

    public function test_ics_keeps_all_day_dates_and_expands_series_with_exceptions(): void
    {
        $events = app(IcalendarEvents::class)->read($this->feed(), 'Australia/Perth');
        $this->assertCount(3, $events);
        $byTitle = collect($events)->keyBy(fn ($item) => $item->fields['title']);
        $this->assertSame('2026-11-27T04:00:00+00:00', $byTitle['Rescheduled workshop']->fields['starts_at']);
        $this->assertSame('2026-11-21T16:00:00+00:00', $byTitle['Open day']->fields['starts_at']);
        $this->assertSame('2026-11-22T16:00:00+00:00', $byTitle['Open day']->fields['ends_at']);
        $this->assertTrue($byTitle['Open day']->fields['all_day']);

        $moved = app(IcalendarEvents::class)->read(str_replace('20261127T120000', '20261127T123000', $this->feed()), 'Australia/Perth');
        $this->assertSame(collect($events)->pluck('key')->all(), collect($moved)->pluck('key')->all());
    }

    public function test_private_and_redirecting_feed_destinations_are_rejected(): void
    {
        $private = new class extends PublicFeedClient
        {
            protected function addresses(string $host): array
            {
                return ['93.184.216.34', '127.0.0.1'];
            }
        };
        Http::fake();
        foreach (['https://internal.test/feed', 'http://public.test/feed', 'https://localhost/feed', 'https://public.test:8443/feed'] as $url) {
            try {
                $private->get($url);
                $this->fail('An unsafe address was accepted.');
            } catch (ImportException) {
                $this->assertTrue(true);
            }
        }
        Http::assertNothingSent();

        $public = new class extends PublicFeedClient
        {
            protected function addresses(string $host): array
            {
                return ['93.184.216.34'];
            }
        };
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['https://public.test/feed?token=fixture' => Http::response('', 302, ['Location' => 'http://127.0.0.1/'])]);
        try {
            $public->get('https://public.test/feed?token=fixture');
            $this->fail('A redirect was accepted.');
        } catch (ImportException) {
            Http::assertSentCount(1);
            Http::assertSent(fn ($request) => $request->url() === 'https://public.test/feed?token=fixture');
        }
    }

    public function test_csv_validates_preview_and_reimport_does_not_overwrite_local_edits(): void
    {
        [$owner, $team] = $this->workspace();
        $csv = fn (string $title) => UploadedFile::fake()->createWithContent('events.csv', "title,starts_at,external_id\n{$title},2026-12-02T10:00,workshop");
        $this->actingAs($owner)->postJson('/integrations/imports/csv/preview', [
            'file' => $csv('Garden workshop'), 'timezone' => 'Australia/Perth',
        ])->assertOk()->assertJsonPath('count', 1)->assertJsonPath('sample.0.starts_at', '2026-12-02T02:00:00+00:00');
        $bad = UploadedFile::fake()->createWithContent('bad.csv', "title,starts_at\nBroken,next Thursday");
        $this->postJson('/integrations/imports/csv/preview', ['file' => $bad, 'timezone' => 'UTC'])
            ->assertOk()->assertJsonPath('count', 0)->assertJsonCount(1, 'errors');
        $this->post('/integrations/imports/csv', ['file' => $csv('Garden workshop'), 'timezone' => 'Australia/Perth'])->assertRedirect();

        $this->setSchema($team->schema_name);
        $event = Event::firstOrFail();
        $event->forceFill(['title' => 'Local title', 'local_overrides' => ['title']])->save();
        DB::select("SELECT set_config('search_path', 'public', false)");
        $this->post('/integrations/imports/csv', ['file' => $csv('Updated upstream'), 'timezone' => 'Australia/Perth'])->assertRedirect();

        $this->getJson('/api/v1/events?source=csv')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', 'Local title')->assertJsonPath('data.0.status', 'draft');
        $this->setSchema($team->schema_name);
        $this->assertSame('Updated upstream', $event->fresh()->source_snapshot['title']);
    }

    public function test_owners_create_private_feed_connections_and_members_cannot_read_credentials(): void
    {
        [$owner, $team] = $this->workspace();
        Queue::fake();
        $secretUrl = 'https://calendar.example.test/feed.ics?key=private-token';
        $this->actingAs($owner)->post('/integrations/imports/ics', ['name' => 'Workshops', 'timezone' => 'UTC', 'feed_url' => $secretUrl])->assertRedirect();
        Queue::assertPushedOnce(SyncInboundEvents::class);
        $this->get('/integrations/imports')->assertOk()->assertDontSee('private-token');
        $this->setSchema($team->schema_name);
        $this->assertStringNotContainsString($secretUrl, DB::table('provider_connections')->value('credentials'));
        DB::select("SELECT set_config('search_path', 'public', false)");

        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'editor']);
        $member->switchTeam($team);
        $this->actingAs($member)->get('/integrations/imports')->assertForbidden();
        $this->post('/integrations/imports/ics', ['name' => 'Workshops'])->assertForbidden();
    }

    public function test_published_outlook_calendar_imports_as_a_regular_calendar_feed(): void
    {
        [$owner, $team] = $this->workspace();
        Queue::fake();
        $this->app->instance(PublicFeedClient::class, new class extends PublicFeedClient
        {
            protected function addresses(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
        $url = 'https://outlook.office365.com/owa/calendar/fixture/calendar.ics';
        $feed = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Microsoft Corporation//Outlook//EN',
            'BEGIN:VEVENT', 'UID:open-day@example.test', 'DTSTAMP:20261101T000000Z',
            'DTSTART;TZID=W. Australia Standard Time:20261203T100000',
            'DTEND;TZID=W. Australia Standard Time:20261203T110000',
            'SUMMARY:Outlook open day', 'END:VEVENT', 'END:VCALENDAR', '',
        ]);
        Http::fake([$url => Http::response($feed, 200, ['Content-Type' => 'text/calendar'])]);

        $this->actingAs($owner)->post('/integrations/imports/ics', [
            'name' => 'Outlook calendar feed', 'timezone' => 'Australia/Perth', 'feed_url' => $url,
        ])->assertRedirect();

        $this->setSchema($team->schema_name);
        $connection = ProviderConnection::firstOrFail();
        $this->assertSame('ics', $connection->provider);
        DB::select("SELECT set_config('search_path', 'public', false)");
        (new SyncInboundEvents($team->id, $connection->id))->handle(app(ImportWriter::class));

        $this->getJson('/api/v1/events?source=ics')->assertOk()
            ->assertJsonPath('data.0.title', 'Outlook open day')
            ->assertJsonPath('data.0.starts_at', '2026-12-03T02:00:00+00:00')
            ->assertJsonPath('data.0.status', 'draft');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === $url && $request->method() === 'GET');
    }

    public function test_wordpress_reads_public_paginated_events_and_preserves_all_day_dates(): void
    {
        $fixture = new class extends PublicFeedClient
        {
            public array $requests = [];

            public function get(string $url, array $query = []): string
            {
                $this->requests[] = [$url, $query['page']];

                return json_encode(['total_pages' => 2, 'events' => [[
                    'id' => $query['page'], 'title' => 'Community &amp; garden',
                    'timezone' => 'Australia/Perth', 'all_day' => true,
                    'start_date' => '2026-11-30 00:00:00', 'end_date' => '2026-11-30 23:59:59',
                    'venue' => ['venue' => 'Community Hall'],
                    'organizer' => [['organizer' => 'Garden Club'], ['organizer' => 'Community Hall']],
                    'categories' => [['name' => 'Outdoors']],
                ]]]);
            }
        };
        $events = (new WordPressEvents($fixture))->read('https://wordpress.example.test/community', 'UTC');
        $this->assertSame([['https://wordpress.example.test/community/wp-json/tribe/events/v1/events', 1], ['https://wordpress.example.test/community/wp-json/tribe/events/v1/events', 2]], $fixture->requests);
        $this->assertSame('Community & garden', $events[0]->fields['title']);
        $this->assertSame('2026-11-30T16:00:00+00:00', $events[0]->fields['ends_at']);
        $this->assertSame(['organiser' => 'Garden Club, Community Hall', 'categories' => 'Outdoors'], $events[0]->metadata);
    }

    public function test_eventbrite_creates_an_editable_template_and_reuses_it_on_refresh(): void
    {
        [$user, $team] = $this->workspace();
        Queue::fake();
        $settings = ['name' => 'Tickets', 'timezone' => 'Australia/Perth', 'organization_id' => '123', 'create_template' => true];
        $this->actingAs($user)->post('/integrations/imports/eventbrite', $settings + ['api_key' => 'eventbrite-fixture'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->setSchema($team->schema_name);
        $connection = ProviderConnection::firstOrFail();
        $template = EventTemplate::findOrFail($connection->template_id);
        $this->assertSame('eventnest', $template->source);
        $this->assertCount(3, $template->fields);
        $mapping = array_flip($connection->field_mapping);
        DB::select("SELECT set_config('search_path', 'public', false)");
        $this->put('/integrations/imports/'.$connection->id, $settings)->assertRedirect()->assertSessionHasNoErrors();
        $this->put('/event-templates/'.$template->id, [
            'name' => 'Editable ticketed events', 'fields' => $template->fields, 'layout' => $template->layout,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $item = [
            'id' => '42', 'name' => ['text' => 'Ticketed workshop'],
            'start' => ['utc' => '2026-12-01T02:00:00Z', 'timezone' => 'Australia/Perth'],
            'url' => 'https://www.eventbrite.com/e/ticketed-workshop',
            'capacity' => 24, 'venue' => ['name' => 'Community Hall'],
        ];
        Http::fake(['https://www.eventbriteapi.com/*' => Http::sequence()
            ->push(['pagination' => ['has_more_items' => true, 'continuation' => 'next'], 'events' => [$item]])
            ->push(['pagination' => ['has_more_items' => false], 'events' => []])
            ->push(['pagination' => ['has_more_items' => false], 'events' => [array_replace($item, ['capacity' => 35])]])
            ->push(['pagination' => ['has_more_items' => false], 'events' => [$item]])]);
        $job = new SyncInboundEvents($team->id, $connection->id);
        $job->handle(app(ImportWriter::class));
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
        $this->setSchema($team->schema_name);
        $this->assertSame('connected', $connection->fresh()->status);
        $this->assertSame(1, EventTemplate::count());
        $this->assertSame('Editable ticketed events', $template->fresh()->name);
        $event = Event::firstOrFail();
        $this->assertSame($template->id, $event->template_id);
        $this->assertEquals([
            $mapping['source_url'] => $item['url'],
            $mapping['venue_name'] => 'Community Hall',
            $mapping['capacity'] => 24,
        ], $event->event_data);
        $event->forceFill([
            'event_data' => array_replace($event->event_data, [$mapping['capacity'] => 20]),
            'local_overrides' => ['event_data'],
        ])->save();
        $job->handle(app(ImportWriter::class));
        $this->setSchema($team->schema_name);
        $this->assertSame(20, $event->fresh()->event_data[$mapping['capacity']]);
        $this->assertSame(35, $event->fresh()->source_snapshot['capacity']);
        $event->delete();
        $job->handle(app(ImportWriter::class));
        $this->setSchema($team->schema_name);
        $this->assertSame(0, Event::count());
        $this->assertSame(1, Event::withTrashed()->count());
        Http::assertSent(fn ($request) => str_contains($request->url(), '/organizations/123/events/?')
            && ($request['continuation'] ?? '') === 'next' && $request->hasHeader('Authorization', 'Bearer eventbrite-fixture'));
        Http::assertNotSent(fn ($request) => $request->method() !== 'GET');
    }

    public function test_wordpress_starter_template_is_explicit_and_maps_only_wordpress_fields(): void
    {
        [$user, $team] = $this->workspace();
        Queue::fake();
        $form = ['name' => 'Community listings', 'timezone' => 'UTC', 'feed_url' => 'https://wordpress.example.test'];
        $this->actingAs($user)->postJson('/integrations/imports/ics', $form + ['create_template' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('create_template');
        $this->post('/integrations/imports/wordpress', $form)->assertRedirect()->assertSessionHasNoErrors();
        $this->setSchema($team->schema_name);
        $connection = ProviderConnection::firstOrFail();
        $this->assertNull($connection->template_id);
        $this->assertSame(0, EventTemplate::count());
        DB::select("SELECT set_config('search_path', 'public', false)");
        $this->put('/integrations/imports/'.$connection->id, $form + ['create_template' => true])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->setSchema($team->schema_name);
        $connection->refresh();
        $this->assertEqualsCanonicalizing(['source_url', 'organiser', 'categories'], array_values($connection->field_mapping));
        $this->assertSame(3, FieldTemplate::count());
        $mapping = array_flip($connection->field_mapping);
        DB::select("SELECT set_config('search_path', 'public', false)");
        $this->putJson('/integrations/imports/'.$connection->id, $form + [
            'template_id' => $connection->template_id, 'field_mapping' => [$mapping['organiser'] => 'capacity'],
        ])->assertUnprocessable()->assertJsonValidationErrors('field_mapping.'.$mapping['organiser']);
    }
}
