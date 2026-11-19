<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Event;
use App\Models\User;
use App\Services\ProvisionTeam;
use App\Traits\SchemaManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkspaceApiWritesTest extends TestCase
{
    use RefreshDatabase, SchemaManagement;

    private function key(User $user, array $permissions, bool $recoverable = false): string
    {
        $secret = $this->actingAs($user)->postJson('/user/api-tokens', [
            'name' => 'Automation', 'permissions' => $permissions, 'recoverable' => $recoverable,
        ])->assertCreated()->assertHeader('Cache-Control', 'no-store, private')->json('token');
        $this->app['auth']->forgetGuards();

        return $secret;
    }

    private function bearer(string $secret): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($secret);
    }

    public function test_read_and_write_permissions_are_independent_and_key_workspace_is_stable(): void
    {
        $owner = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'First', true);
        $other = app(ProvisionTeam::class)->create($owner, 'Second');
        $this->assertTrue($owner->switchTeam($team));
        $readOnly = $this->key($owner, ['read']);
        $creator = $this->key($owner, ['read', 'events:create']);
        $editor = $this->key($owner, ['read', 'events:update']);

        $this->bearer($readOnly)->postJson('/api/v1/events', ['title' => 'Denied'])->assertForbidden();
        $this->bearer($editor)->postJson('/api/v1/events', ['title' => 'Denied'])->assertForbidden();
        $this->assertTrue($owner->switchTeam($other));
        $created = $this->bearer($creator)->postJson('/api/v1/events', ['title' => 'Native workshop'])
            ->assertCreated()->assertJsonPath('data.status', 'draft');
        $id = $created->json('data.id');
        $created->assertHeader('Location', route('api.v1.events.show', $id));
        $this->bearer($creator)->putJson('/api/v1/events/'.$id, ['title' => 'Denied', 'status' => 'draft'])->assertForbidden();
        $this->bearer($editor)->putJson('/api/v1/events/'.$id, ['title' => 'Updated', 'status' => 'draft'])
            ->assertOk()->assertJsonPath('data.title', 'Updated');
        $this->bearer($editor)->getJson('/api/v1/events/'.$id)->assertOk()->assertJsonPath('data.title', 'Updated');
        $this->bearer($readOnly)->getJson('/api/v1/events/'.$id)->assertNotFound();
        $this->setSchema($team->schema_name);
        $this->assertSame('Updated', Event::findOrFail($id)->title);
    }

    public function test_admins_manage_fields_and_templates_and_event_writes_validate_answers(): void
    {
        $owner = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'Workshops', true);
        $secret = $this->key($owner, ['read', ...ApiToken::WRITE_ABILITIES]);
        $field = ['field_id' => 'audience', 'label' => 'Audience', 'type' => 'text', 'required' => true];
        $fieldId = $this->bearer($secret)->postJson('/api/v1/field-templates', $field)->assertCreated()->json('data.id');
        $this->bearer($secret)->putJson('/api/v1/field-templates/'.$fieldId, array_replace($field, ['field_id' => 'renamed']))
            ->assertUnprocessable()->assertJsonValidationErrors('field_id');
        $this->bearer($secret)->putJson('/api/v1/field-templates/'.$fieldId, array_replace($field, ['label' => 'Audience details']))
            ->assertOk()->assertJsonPath('data.field_id', 'audience');
        $template = ['name' => 'Workshop', 'fields' => [$fieldId], 'layout' => [[$fieldId], []]];
        $templateId = $this->bearer($secret)->postJson('/api/v1/event-templates', $template)->assertCreated()->json('data.id');
        $this->bearer($secret)->putJson('/api/v1/event-templates/'.$templateId, array_replace($template, ['name' => 'Workshop template']))
            ->assertOk()->assertJsonPath('data.name', 'Workshop template');
        $this->bearer($secret)->getJson('/api/v1/field-templates/'.$fieldId)->assertOk();
        $this->bearer($secret)->getJson('/api/v1/event-templates/'.$templateId)->assertOk();
        $this->bearer($secret)->postJson('/api/v1/events', ['template_id' => $templateId, 'title' => 'Needs an audience'])
            ->assertUnprocessable()->assertJsonValidationErrors('event_data.audience');
        $eventId = $this->bearer($secret)->postJson('/api/v1/events', [
            'template_id' => $templateId, 'title' => 'Open workshop', 'custom_fields' => ['audience' => 'Adults'],
        ])->assertCreated()->assertJsonPath('data.custom_fields.audience', 'Adults')->json('data.id');

        $this->setSchema($team->schema_name);
        Event::findOrFail($eventId)->forceFill(['source' => 'humanitix', 'external_id' => 'fixture'])->save();
        DB::statement('SET search_path TO public');
        $this->bearer($secret)->putJson('/api/v1/events/'.$eventId, [
            'template_id' => $templateId, 'title' => 'Local title', 'status' => 'draft', 'custom_fields' => ['audience' => 'Adults'],
        ])->assertOk()->assertJsonPath('data.source.external_id', 'fixture')->assertJsonFragment(['local_overrides' => ['title']]);
    }

    public function test_recoverable_events_only_key_cannot_read_templates_or_gain_write_access(): void
    {
        $owner = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'Workshops', true);
        $secret = $this->key($owner, ['events:read'], recoverable: true);
        $id = ApiToken::findToken($secret)->id;
        $this->assertSame($team->id, ApiToken::findToken($secret)->workspace_id);
        $this->assertStringNotContainsString($secret, ApiToken::findToken($secret)->getRawOriginal('recoverable_secret'));
        $this->bearer($secret)->getJson('/api/v1/events')->assertOk();
        $this->bearer($secret)->getJson('/api/event-templates')->assertForbidden();
        $this->bearer($secret)->getJson('/api/field-templates')->assertForbidden();
        $this->bearer($secret)->getJson('/api/user')->assertForbidden();
        $this->bearer($secret)->postJson('/api/v1/events', ['title' => 'Denied'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->actingAs($owner, 'web')->getJson('/api/v1/events')->assertUnauthorized();
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->actingAs(User::factory()->create())->postJson("/user/api-tokens/{$id}/reveal")->assertNotFound();
        $this->actingAs($owner)->get('/user/api-tokens')->assertOk()->assertDontSee($secret);
        $this->postJson("/user/api-tokens/{$id}/reveal")->assertOk()->assertJsonPath('token', $secret)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->putJson("/user/api-tokens/{$id}", ['permissions' => ['read', 'events:create']])
            ->assertUnprocessable()->assertJsonValidationErrors('permissions');
        $this->postJson('/user/api-tokens', [
            'name' => 'Invalid recovery', 'permissions' => ['events:create'], 'recoverable' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('recoverable');
        $this->postJson('/user/api-tokens', [
            'name' => 'Mixed scope', 'permissions' => ['events:read', 'events:create'],
        ])->assertUnprocessable()->assertJsonValidationErrors('permissions');
    }

    public function test_workspace_editors_can_write_events_but_cannot_write_templates(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'Shared', true);
        $team->users()->attach($editor, ['role' => 'editor']);
        $this->assertTrue($editor->switchTeam($team));
        $this->actingAs($editor)->postJson('/user/api-tokens', ['name' => 'Denied', 'permissions' => ['templates:create']])->assertForbidden();
        $secret = $this->key($editor, ['events:create']);
        $this->bearer($secret)->postJson('/api/v1/events', ['title' => 'Editor event'])->assertCreated();
        $team->users()->detach($editor);
        $this->bearer($secret)->postJson('/api/v1/events', ['title' => 'Former editor'])->assertForbidden();
    }
}
