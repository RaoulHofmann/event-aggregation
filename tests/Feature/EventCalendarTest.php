<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\User;
use App\Services\EventOccurrences;
use App\Services\ProvisionTeam;
use App\Traits\SchemaManagement;
use Carbon\CarbonImmutable as Date;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventCalendarTest extends TestCase
{
    use RefreshDatabase, SchemaManagement;

    private function workspace(): User
    {
        $user = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($user, 'Calendar', true);
        $this->setSchema($team->schema_name);
        $this->actingAs($user);

        return $user;
    }

    private function series(array $overrides = []): Event
    {
        $event = Event::create(['event_data' => []])->forceFill(array_replace([
            'title' => 'Weekly class', 'source' => 'eventnest', 'timezone' => 'America/New_York',
            'starts_at' => Date::parse('2027-03-07 10:00', 'America/New_York')->utc(),
            'ends_at' => Date::parse('2027-03-07 11:00', 'America/New_York')->utc(),
            'recurrence' => ['frequency' => 'weekly', 'interval' => 1, 'weekdays' => [7], 'count' => 3],
        ], $overrides));
        $event->save();

        return $event;
    }

    public function test_weekly_series_preserves_wall_clock_across_dst_and_stable_ids(): void
    {
        $this->workspace();
        $event = $this->series();
        $items = app(EventOccurrences::class)->forEvent($event, Date::parse('2027-03-01'), Date::parse('2027-04-01'));
        $this->assertCount(3, $items);
        $this->assertSame('2027-03-07T15:00:00+00:00', $items[0]['starts_at']);
        $this->assertSame('2027-03-14T14:00:00+00:00', $items[1]['starts_at']);
        $this->assertSame($event->id.'@2027-03-14', $items[1]['id']);
    }

    public function test_month_end_and_leap_day_skip_invalid_dates(): void
    {
        $this->workspace();
        $service = app(EventOccurrences::class);
        $event = $this->series(['starts_at' => Date::parse('2028-01-31T10:00Z'), 'ends_at' => Date::parse('2028-01-31T11:00Z'), 'timezone' => 'UTC', 'recurrence' => ['frequency' => 'monthly', 'interval' => 1, 'count' => 3]]);
        $items = $service->forEvent($event, Date::parse('2028-01-01'), Date::parse('2028-07-01'));
        $this->assertSame(['2028-01-31', '2028-03-31', '2028-05-31'], array_column($items, 'occurrence_date'));
        $event->forceFill(['starts_at' => Date::parse('2028-02-29T10:00Z'), 'ends_at' => Date::parse('2028-02-29T11:00Z'), 'recurrence' => ['frequency' => 'yearly', 'interval' => 1, 'count' => 2]]);
        $this->assertSame(['2028-02-29', '2032-02-29'], array_column($service->forEvent($event, Date::parse('2028-01-01'), Date::parse('2033-01-01')), 'occurrence_date'));
    }

    public function test_moved_occurrences_enter_new_window_and_skips_do_not_shift_count(): void
    {
        $this->workspace();
        $event = $this->series();
        DB::table('event_exceptions')->insert([
            ['event_id' => $event->id, 'occurrence_date' => '2027-03-07', 'cancelled' => false, 'starts_at' => '2027-05-01 14:00:00+00', 'ends_at' => '2027-05-01 15:00:00+00'],
            ['event_id' => $event->id, 'occurrence_date' => '2027-03-14', 'cancelled' => true, 'starts_at' => null, 'ends_at' => null],
        ]);
        $service = app(EventOccurrences::class);
        $this->assertCount(1, $service->window(Date::parse('2027-05-01'), Date::parse('2027-05-02')));
        $this->assertSame(['2027-03-21'], array_column($service->forEvent($event, Date::parse('2027-03-01'), Date::parse('2027-04-01')), 'occurrence_date'));
    }

    public function test_basic_event_needs_no_template_and_published_events_need_schedule(): void
    {
        $this->workspace();
        DB::select("SELECT set_config('search_path','public',false)");
        $this->post('/events', ['title' => 'Basic draft', 'event_data' => [], 'status' => 'draft'])->assertRedirect();
        $this->postJson('/events', ['title' => 'Incomplete', 'event_data' => [], 'status' => 'published'])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->getJson('/api/v1/events')->assertOk()->assertJsonPath('data.0.title', 'Basic draft')->assertJsonPath('data.0.template.id', null);
    }

    public function test_occurrence_api_bounds_isolation_and_soft_deletes(): void
    {
        $user = $this->workspace();
        $event = $this->series();
        DB::select("SELECT set_config('search_path','public',false)");
        $this->getJson('/api/v1/occurrences?from=2027-03-01&to=2027-04-01')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/occurrences?from=2027-01-01&to=2028-01-01')->assertUnprocessable();
        $originalTeam = $user->currentTeam;
        app(ProvisionTeam::class)->create($user, 'Other', false);
        $this->getJson('/api/v1/occurrences?from=2027-03-01&to=2027-04-01')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/events/'.$event->id)->assertNotFound();
        $user->switchTeam($originalTeam);
        $this->delete('/events/'.$event->id)->assertRedirect();
        $this->getJson('/api/v1/occurrences?from=2027-03-01&to=2027-04-01')->assertJsonCount(0, 'data');
    }

    public function test_exception_validation_and_imported_events_are_protected(): void
    {
        $this->workspace();
        $event = $this->series();
        DB::select("SELECT set_config('search_path','public',false)");
        $this->postJson('/events/'.$event->id.'/exceptions', ['occurrence_date' => '2027-03-08', 'cancelled' => true])->assertUnprocessable();
        $this->post('/events/'.$event->id.'/exceptions', ['occurrence_date' => '2027-03-14', 'cancelled' => true])->assertRedirect();
        $this->getJson('/api/v1/occurrences?from=2027-03-01&to=2027-04-01')->assertJsonCount(2, 'data');
        $this->setSchema(auth()->user()->currentTeam->schema_name);
        $event->forceFill(['source' => 'humanitix', 'recurrence' => null])->save();
        DB::select("SELECT set_config('search_path','public',false)");
        $this->postJson('/events/'.$event->id.'/exceptions', ['occurrence_date' => '2027-03-21', 'cancelled' => true])->assertForbidden();
    }

    public function test_all_day_and_overlap_end_boundaries(): void
    {
        $this->workspace();
        $event = $this->series(['starts_at' => Date::parse('2027-03-13 00:00', 'America/New_York')->utc(), 'ends_at' => Date::parse('2027-03-15 00:00', 'America/New_York')->utc(), 'all_day' => true, 'recurrence' => ['frequency' => 'weekly', 'weekdays' => [6], 'interval' => 1, 'count' => 2]]);
        $items = app(EventOccurrences::class)->forEvent($event, Date::parse('2027-03-01'), Date::parse('2027-04-01'));
        $this->assertSame('2027-03-22', $items[1]['end_date']);
        $this->assertCount(0, app(EventOccurrences::class)->forEvent($event, Date::parse('2027-03-22 00:00', 'America/New_York'), Date::parse('2027-03-23 00:00', 'America/New_York')));
    }

    public function test_repeat_until_monthly_weekday_and_interval(): void
    {
        $this->workspace();
        $event = $this->series(['starts_at' => Date::parse('2027-01-12T10:00Z'), 'ends_at' => Date::parse('2027-01-12T11:00Z'), 'timezone' => 'UTC', 'recurrence' => ['frequency' => 'monthly', 'monthly_mode' => 'weekday', 'interval' => 2, 'count' => 20, 'until' => '2027-05-11']]);
        $items = app(EventOccurrences::class)->forEvent($event, Date::parse('2027-01-01'), Date::parse('2028-01-01'));
        $this->assertSame(['2027-01-12', '2027-03-09', '2027-05-11'], array_column($items, 'occurrence_date'));
    }

    public function test_repeat_preview_and_invalid_rules(): void
    {
        $this->workspace();
        DB::select("SELECT set_config('search_path','public',false)");
        $data = ['title' => 'Preview', 'event_data' => [], 'starts_at' => '2027-03-07T10:00', 'ends_at' => '2027-03-07T11:00', 'timezone' => 'America/New_York', 'recurrence' => ['frequency' => 'daily', 'interval' => 2, 'count' => 3]];
        $this->postJson('/events/preview', $data)->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.2.occurrence_date', '2027-03-11');
        $this->getJson('/api/v1/events')->assertJsonPath('meta.total', 0);
        $data['recurrence']['interval'] = 0;
        $this->postJson('/events', $data)->assertUnprocessable()->assertJsonValidationErrors('recurrence.interval');
        $data['recurrence'] = ['frequency' => 'weekly', 'interval' => 1, 'count' => 3, 'weekdays' => []];
        $this->postJson('/events', $data)->assertUnprocessable()->assertJsonValidationErrors('recurrence.weekdays');
    }

    public function test_series_schedule_cannot_detach_existing_exceptions(): void
    {
        $user = $this->workspace();
        $event = $this->series();
        DB::table('event_exceptions')->insert(['event_id' => $event->id, 'occurrence_date' => '2027-03-14', 'cancelled' => true]);
        DB::select("SELECT set_config('search_path','public',false)");
        $data = ['title' => 'Changed', 'event_data' => [], 'starts_at' => '2027-03-07T12:00', 'ends_at' => '2027-03-07T13:00', 'timezone' => 'America/New_York', 'recurrence' => $event->recurrence];
        $this->putJson('/events/'.$event->id, $data)->assertUnprocessable()->assertJsonValidationErrors('recurrence');
        $this->getJson('/api/v1/occurrences?from=2027-03-01&to=2027-04-01')->assertJsonCount(2, 'data');
    }

    public function test_basic_templates_and_legacy_dates_remain_safe(): void
    {
        $user = $this->workspace();
        DB::select("SELECT set_config('search_path','public',false)");
        $this->post('/event-templates', ['name' => 'Workshop', 'fields' => [], 'layout' => [[], []]])->assertRedirect();
        $this->setSchema($user->currentTeam->schema_name);
        $field = FieldTemplate::create(['label' => 'Legacy start', 'field_id' => 'start_date', 'type' => 'datetime', 'required' => false]);
        $template = EventTemplate::create(['name' => 'Legacy', 'fields' => [$field->id], 'layout' => [[$field->id], []]]);
        $event = Event::create(['template_id' => $template->id, 'event_data' => ['start_date' => '2026-01-01T10:00']]);
        DB::select("SELECT set_config('search_path','public',false)");
        $this->put('/events/'.$event->id, ['title' => 'Still undated', 'template_id' => $template->id, 'event_data' => $event->event_data, 'starts_at' => null])->assertRedirect();
        $this->getJson('/events/'.$event->id)->assertJsonPath('event_data.start_date', '2026-01-01T10:00')->assertJsonPath('starts_at', null);
        $this->postJson('/events', ['title' => 'DST gap', 'event_data' => [], 'starts_at' => '2027-03-14T02:30', 'timezone' => 'America/New_York'])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->postJson('/event-templates', ['name' => 'Invalid layout', 'fields' => [$field->id], 'layout' => [[], []]])->assertUnprocessable()->assertJsonValidationErrors('layout');
    }

    public function test_schedule_preview_does_not_require_unfinished_custom_answers(): void
    {
        $this->workspace();
        $field = FieldTemplate::create(['label' => 'Audience', 'field_id' => 'audience', 'type' => 'text', 'required' => true]);
        $template = EventTemplate::create(['name' => 'Workshop', 'fields' => [$field->id], 'layout' => [[$field->id], []]]);
        DB::select("SELECT set_config('search_path','public',false)");
        $payload = ['title' => 'Preview only', 'template_id' => $template->id, 'event_data' => [], 'starts_at' => '2026-11-21T10:00', 'ends_at' => '2026-11-21T12:00', 'timezone' => 'Australia/Perth', 'recurrence' => ['frequency' => 'weekly', 'weekdays' => [6], 'interval' => 1, 'count' => 3]];
        $this->postJson('/events/preview', $payload)->assertOk()->assertJsonCount(3, 'data');
        $this->postJson('/events', $payload)->assertUnprocessable()->assertJsonValidationErrors('event_data.audience');
        $this->getJson('/api/v1/events')->assertJsonPath('meta.total', 0);
    }
}
