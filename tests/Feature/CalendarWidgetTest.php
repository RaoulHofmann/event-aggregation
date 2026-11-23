<?php

namespace Tests\Feature;

use App\Models\CalendarWidget;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\User;
use App\Models\WorkspaceImage;
use App\Services\ProvisionTeam;
use App\Traits\SchemaManagement;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarWidgetTest extends TestCase
{
    use RefreshDatabase, SchemaManagement;

    public function test_public_widget_exposes_only_published_occurrences_matching_workspace_filters(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-19T03:00:00Z'));
        $owner = User::factory()->create();
        $other = app(ProvisionTeam::class)->create($owner, 'Other');
        $workspace = app(ProvisionTeam::class)->create($owner, 'Public programme');
        $this->setSchema($other->schema_name);
        Event::create(['event_data' => []])->forceFill(['title' => 'Other workspace event', 'status' => 'published', 'starts_at' => now()->addDay()])->save();
        $this->setSchema($workspace->schema_name);
        $template = EventTemplate::create(['name' => 'Workshops', 'fields' => [], 'layout' => [[]]]);
        $event = Event::create(['event_data' => ['private_answer' => 'Private answer'], 'template_id' => $template->id]);
        $event->forceFill([
            'title' => 'Published workshop', 'location' => 'Workshop hall', 'description' => 'Private description',
            'starts_at' => '2026-11-20 02:00:00', 'ends_at' => '2026-11-20 03:00:00',
            'timezone' => 'Australia/Perth', 'status' => 'published',
            'recurrence' => ['frequency' => 'daily', 'count' => 2],
        ])->save();
        DB::table('event_exceptions')->insert([
            'event_id' => $event->id, 'occurrence_date' => '2026-11-20', 'cancelled' => false,
            'starts_at' => '2026-11-20 06:30:00', 'ends_at' => '2026-11-20 07:30:00',
        ]);
        $excludedIds = [];
        foreach (['draft' => 'Draft listing', 'cancelled' => 'Cancelled listing'] as $status => $title) {
            $excluded = Event::create(['event_data' => [], 'template_id' => $template->id]);
            $excluded->forceFill(['title' => $title, 'status' => $status, 'starts_at' => now()->addDay()])->save();
            $excludedIds[] = $excluded->id;
        }
        Event::create(['event_data' => [], 'template_id' => $template->id])->forceFill([
            'title' => 'Imported listing', 'source' => 'google_calendar', 'status' => 'published', 'starts_at' => now()->addDay(),
        ])->save();
        Event::create(['event_data' => []])->forceFill(['title' => 'Different template', 'status' => 'published', 'starts_at' => now()->addDay()])->save();
        DB::statement('SET search_path TO public');

        $settings = ['title' => 'Workshop dates', 'source' => 'eventnest', 'template_id' => $template->id];
        $this->get('/embed/upcoming/'.str_repeat('x', 40))->assertNotFound();
        $this->actingAs($owner)->put('/events/widget', $settings)->assertRedirect(route('events.widget'));
        $widget = CalendarWidget::firstOrFail();
        $url = route('widgets.upcoming.show', $widget->public_id, absolute: false);
        $this->put('/events/widget', $settings)->assertRedirect();
        $this->assertSame($widget->public_id, $widget->fresh()->public_id);
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertOk()->assertHeader('X-EventJunction-Embed', '1')->assertHeaderMissing('Set-Cookie')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Workshop dates')->assertSee('Published workshop')->assertSee('Workshop hall')
            ->assertSee('2:30pm AWST')->assertSee('10:00am AWST')
            ->assertDontSee('Draft listing')->assertDontSee('Cancelled listing')->assertDontSee('Different template')
            ->assertDontSee('Imported listing')->assertDontSee('Other workspace event')->assertDontSee('Private answer')->assertDontSee('Private description');
        $detail = $url.'?event='.rawurlencode($event->id.'@2026-11-20');
        $this->get($detail)->assertOk()->assertHeader('X-EventJunction-Embed', '1')
            ->assertHeaderMissing('Set-Cookie')->assertSee('Back to events')->assertSee('2:30pm AWST')
            ->assertSee('3:30pm AWST')->assertDontSee('Private description')->assertDontSee('Private answer');
        foreach ($excludedIds as $id) {
            $this->get($url.'?event='.rawurlencode($id.'@2026-11-20'))->assertNotFound()->assertSee('Back to events');
        }
        $this->get($url.'?event[]=test')->assertNotFound();
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
        $workspace->forceFill(['suspended_at' => now()])->save();
        $this->get($url)->assertNotFound();
        $workspace->forceFill(['suspended_at' => null])->save();
        $this->actingAs($owner)->delete('/events/widget')->assertRedirect(route('events.widget'));
        $this->get($url)->assertNotFound();
        $this->get($detail)->assertNotFound();
        $this->put('/events/widget', $settings)->assertRedirect();
        $this->assertNotSame($widget->public_id, CalendarWidget::firstOrFail()->public_id);
    }

    public function test_widget_settings_are_owner_only_and_demo_is_public(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = app(ProvisionTeam::class)->create($owner, 'Public programme');
        $workspace->users()->attach($member, ['role' => 'editor']);
        $member->switchTeam($workspace);
        $this->get('/embed/upcoming/example')->assertOk()->assertSee('Example calendar')->assertHeaderMissing('Set-Cookie');
        $this->get('/embed/upcoming/example?theme=ink&event=garden-workshop')
            ->assertOk()->assertSee('theme-ink')->assertSee('Plant seasonal seedlings')->assertSee('href="/embed/upcoming/example?theme=ink"', false);
        $this->get('/events/widget')->assertRedirect(route('login'));
        $this->actingAs($member)->get('/events/widget')->assertForbidden();
        $this->put('/events/widget', ['title' => 'Member calendar'])->assertForbidden();
        $this->delete('/events/widget')->assertForbidden();
        $this->actingAs($owner)->putJson('/events/widget', ['title' => 'Calendar', 'template_id' => 999])->assertUnprocessable()
            ->assertJsonValidationErrors('template_id');
    }

    public function test_widget_branding_keeps_the_embed_url_and_descriptions_require_opt_in(): void
    {
        $owner = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'Public programme');
        $this->setSchema($team->schema_name);
        $cover = WorkspaceImage::create(['name' => 'banner.png', 'mime' => 'image/png', 'bytes' => 64]);
        $event = Event::create(['event_data' => ['unlisted' => 'Internal note']]);
        $event->forceFill([
            'title' => 'Community session', 'description' => '<script>alert(1)</script> Describe the session.',
            'status' => 'published', 'timezone' => 'UTC', 'starts_at' => now()->addDay()->setTime(12, 0),
            'source_url' => 'https://example.test/private',
            'cover_image_id' => $cover->id, 'cover_public_id' => str_repeat('c', 40),
        ])->save();
        DB::statement('SET search_path TO public');

        $settings = ['title' => 'Community calendar'];
        $this->actingAs($owner)->put('/events/widget', $settings)->assertRedirect();
        $widget = CalendarWidget::firstOrFail();
        $agenda = route('widgets.upcoming.show', $widget->public_id, absolute: false);
        $detail = $agenda.'?event='.rawurlencode($event->id.'@'.$event->starts_at->toDateString());
        $this->get($detail)->assertOk()->assertDontSee('Describe the session.');
        $settings['appearance'] = [
            'theme' => 'sage', 'brand' => 'Neighbourhood group', 'compact' => true, 'show_description' => true, 'show_credit' => false,
        ];
        $this->put('/events/widget', $settings)->assertRedirect();
        $this->assertSame($widget->public_id, $widget->fresh()->public_id);
        $this->get($agenda)->assertOk()->assertSee('theme-sage')->assertSee('agenda compact')
            ->assertSee('Neighbourhood group')->assertDontSee('>EventJunction<', false)->assertDontSee('Describe the session.');
        $this->get($detail)->assertOk()->assertSee('Describe the session.')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Internal note')->assertDontSee('https://example.test/private', false)
            ->assertSee('/media/events/'.$team->id.'/'.str_repeat('c', 40), false);
        $this->put('/events/widget', ['title' => 'Renamed'])->assertRedirect();
        $this->get($agenda)->assertOk()->assertSee('Neighbourhood group');
        $this->putJson('/events/widget', array_replace($settings, ['appearance' => ['theme' => 'invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors('appearance.theme');
    }
}
