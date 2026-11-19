<?php

namespace Tests\Feature;

use App\Models\CalendarWidget;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\User;
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
        foreach (['draft' => 'Draft listing', 'cancelled' => 'Cancelled listing'] as $status => $title) {
            Event::create(['event_data' => [], 'template_id' => $template->id])->forceFill(['title' => $title, 'status' => $status, 'starts_at' => now()->addDay()])->save();
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
        $this->assertSame('public', DB::selectOne('SHOW search_path')->search_path);
        $workspace->forceFill(['suspended_at' => now()])->save();
        $this->get($url)->assertNotFound();
        $workspace->forceFill(['suspended_at' => null])->save();
        $this->actingAs($owner)->delete('/events/widget')->assertRedirect(route('events.widget'));
        $this->get($url)->assertNotFound();
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
        $this->get('/events/widget')->assertRedirect(route('login'));
        $this->actingAs($member)->get('/events/widget')->assertForbidden();
        $this->put('/events/widget', ['title' => 'Member calendar'])->assertForbidden();
        $this->delete('/events/widget')->assertForbidden();
        $this->actingAs($owner)->putJson('/events/widget', ['title' => 'Calendar', 'template_id' => 999])->assertUnprocessable()
            ->assertJsonValidationErrors('template_id');
    }
}
