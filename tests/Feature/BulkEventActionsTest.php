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

class BulkEventActionsTest extends TestCase
{
    use RefreshDatabase, SchemaManagement;

    public function test_bulk_update_preserves_unsupplied_values_and_protects_imported_changes(): void
    {
        $owner = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'Workshops', true);
        $this->setSchema($team->schema_name);
        Event::create(['event_data' => ['audience' => 'Adults']])->forceFill(['title' => 'Native', 'location' => 'Old hall'])->save();
        $imported = Event::create(['event_data' => []]);
        $imported->forceFill(['title' => 'Imported', 'source' => 'ics', 'location' => 'Park', 'local_overrides' => ['description']])->save();
        $ids = Event::orderBy('id')->pluck('id')->all();
        DB::statement('SET search_path TO public');

        $this->actingAs($owner)->from('/events?status=draft')->patch('/events/bulk', [
            'workspace_id' => $team->id, 'ids' => $ids, 'status' => 'cancelled', 'location' => null,
        ])->assertRedirect('/events?status=draft')->assertSessionHasNoErrors();

        $this->setSchema($team->schema_name);
        $this->assertSame(['cancelled', 'cancelled'], Event::orderBy('id')->pluck('status')->all());
        $this->assertSame([null, null], Event::orderBy('id')->pluck('location')->all());
        $this->assertSame(['audience' => 'Adults'], Event::findOrFail($ids[0])->event_data);
        $this->assertSame(['description', 'status', 'location'], $imported->fresh()->local_overrides);
    }

    public function test_bulk_publish_and_delete_are_atomic_and_scoped_to_the_selection_workspace(): void
    {
        $owner = User::factory()->create();
        $other = app(ProvisionTeam::class)->create($owner, 'Other workspace');
        $team = app(ProvisionTeam::class)->create($owner, 'Workshops', true);
        $this->setSchema($other->schema_name);
        $otherEvent = Event::create(['event_data' => []]);
        $this->setSchema($team->schema_name);
        $field = FieldTemplate::create(['label' => 'Audience', 'field_id' => 'audience', 'type' => 'text', 'required' => true]);
        $template = EventTemplate::create(['name' => 'Workshop', 'source' => 'eventnest', 'fields' => [$field->id]]);
        $ready = Event::create(['event_data' => []]);
        $ready->forceFill(['title' => 'Ready', 'starts_at' => now(), 'timezone' => 'UTC'])->save();
        $incomplete = Event::create(['event_data' => [], 'template_id' => $template->id]);
        $incomplete->forceFill(['title' => 'Incomplete', 'starts_at' => now(), 'timezone' => 'UTC', 'source' => 'humanitix'])->save();
        DB::statement('SET search_path TO public');

        $this->actingAs($owner)->patchJson('/events/bulk', [
            'workspace_id' => $team->id, 'ids' => [$ready->id, $incomplete->id], 'status' => 'published',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->deleteJson('/events/bulk', [
            'workspace_id' => $other->id, 'ids' => [$otherEvent->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids');
        $this->deleteJson('/events/bulk', [
            'workspace_id' => $team->id, 'ids' => [$ready->id, $incomplete->id + 100],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids');

        $this->setSchema($team->schema_name);
        $this->assertSame(['draft', 'draft'], Event::orderBy('id')->pluck('status')->all());
        DB::statement('SET search_path TO public');
        $this->delete('/events/bulk', ['workspace_id' => $team->id, 'ids' => [$ready->id, $incomplete->id]])->assertRedirect();
        $this->setSchema($team->schema_name);
        $this->assertSame(0, Event::count());
        $this->assertSame(2, Event::onlyTrashed()->count());
        $this->setSchema($other->schema_name);
        $this->assertNotNull($otherEvent->fresh());
    }
}
