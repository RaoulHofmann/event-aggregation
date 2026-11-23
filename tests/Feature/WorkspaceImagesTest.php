<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\User;
use App\Services\ProvisionTeam;
use App\Traits\SchemaManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkspaceImagesTest extends TestCase
{
    use RefreshDatabase, SchemaManagement;

    public function test_draft_covers_are_private_and_published_urls_follow_the_event(): void
    {
        Storage::fake('workspace_images');
        config(['public-site.url' => 'https://eventjunction.app']);
        $owner = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'Workshop programme');
        $this->actingAs($owner, 'web')->get('/assets')->assertOk();
        $image = $this->upload()->assertCreated()->json('id');
        $this->getJson('/assets/images?search=garden')->assertOk()->assertJsonPath('data.0.id', $image);
        $this->getJson('/assets/images?search=market')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/assets/images/'.$image)->assertOk()->assertHeader('Content-Type', 'image/png');
        Storage::disk('workspace_images')->assertExists("workspaces/{$team->id}/{$image}");

        $this->setSchema($team->schema_name);
        $field = FieldTemplate::create(['field_id' => 'image', 'label' => 'Linked image', 'type' => 'image']);
        $template = EventTemplate::create(['name' => 'Workshop', 'fields' => [$field->id]]);
        DB::statement('SET search_path TO public');
        $linked = 'https://events.example.test/previous-cover.jpg';
        $data = [
            'title' => 'Garden workshop', 'timezone' => 'Australia/Perth', 'starts_at' => '2026-11-29T10:00',
            'template_id' => $template->id, 'event_data' => ['image' => $linked], 'cover_image_id' => $image,
        ];
        $this->post('/events', $data)->assertRedirect(route('events.index'));
        $this->setSchema($team->schema_name);
        $event = Event::sole();
        DB::statement('SET search_path TO public');
        $path = route('events.cover', ['workspace' => $team->id, 'cover' => $event->cover_public_id], absolute: false);

        $this->getJson('/api/v1/events/'.$event->id)->assertOk()
            ->assertJsonPath('data.image_url', null)->assertJsonPath('data.custom_fields.image', $linked);
        $this->get($path)->assertNotFound();
        $this->put('/events/'.$event->id, $data + ['status' => 'published'])->assertRedirect();
        $url = $this->getJson('/api/v1/events/'.$event->id)->assertOk()->json('data.image_url');
        $this->assertSame('https://eventjunction.app'.$path, $url);
        $this->getJson('/api/v1/occurrences?from=2026-11-28&to=2026-11-30')->assertOk()->assertJsonPath('data.0.image_url', $url);
        $this->app['auth']->forgetGuards();
        $this->get($path)->assertOk()->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeaderMissing('Set-Cookie')
            ->assertContent($this->png());
        $this->get('/assets/images/'.$image)->assertRedirect(route('login'));

        $this->actingAs($owner, 'web');
        $this->post('/events', array_replace($data, ['title' => 'Second workshop']))->assertRedirect(route('events.index'));
        $this->deleteJson('/assets/images/'.$image)->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->put('/events/'.$event->id, $data + ['status' => 'draft'])->assertRedirect();
        $this->get($path)->assertNotFound();

        $replacement = $this->upload('replacement.png')->assertCreated()->json('id');
        $this->put('/events/'.$event->id, array_replace($data, ['status' => 'published', 'cover_image_id' => $replacement]))->assertRedirect();
        $this->getJson('/api/v1/events/'.$event->id)->assertOk()->assertJsonPath('data.image_url', $url);
        $this->get($path)->assertOk();
        $this->delete('/events/'.$event->id)->assertRedirect();
        $this->get($path)->assertNotFound();
        $this->deleteJson('/assets/images/'.$replacement)->assertNoContent();
        Storage::disk('workspace_images')->assertMissing("workspaces/{$team->id}/{$replacement}");
    }

    public function test_asset_actions_are_scoped_to_workspaces_and_events_only_keys_cannot_read_the_library(): void
    {
        Storage::fake('workspace_images');
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $team = app(ProvisionTeam::class)->create($owner, 'Gallery');
        $team->users()->attach($editor, ['role' => 'editor']);
        $editor->switchTeam($team);

        $this->actingAs($editor, 'web');
        $this->postJson('/assets/images', ['image' => UploadedFile::fake()->createWithContent('fake.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $image = $this->upload()->assertCreated()->json('id');
        $this->deleteJson('/assets/images/'.$image)->assertForbidden();
        $this->actingAs($owner, 'web')->get('/assets/images/'.$image)->assertOk();
        $secret = $this->postJson('/user/api-tokens', ['name' => 'Event reader', 'permissions' => ['events:read']])
            ->assertCreated()->json('token');
        $this->app['auth']->forgetGuards();
        $this->withToken($secret)->getJson('/assets/images/'.$image)->assertUnauthorized();
        $this->getJson('/assets/images')->assertUnauthorized();
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $outsider = User::factory()->create();
        app(ProvisionTeam::class)->create($outsider, 'Other workspace');
        $this->actingAs($outsider, 'web')->getJson('/assets/images')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/assets/images/'.$image)->assertNotFound();
        $this->postJson('/events', ['title' => 'Outsider event', 'event_data' => [], 'cover_image_id' => $image])
            ->assertUnprocessable()->assertJsonValidationErrors('cover_image_id');
    }

    public function test_remote_storage_uses_authenticated_requests_and_keeps_files_off_the_local_disk(): void
    {
        $owner = User::factory()->create();
        app(ProvisionTeam::class)->create($owner, 'Remote images');
        Storage::fake('workspace_images');
        config(['filesystems.workspace_images' => [
            'remote' => true, 'url' => 'https://test-project.supabase.co', 'key' => 'fixture-storage-key', 'bucket' => 'eventjunction-images',
        ]]);
        $base = 'https://test-project.supabase.co/storage/v1/object';
        Http::preventStrayRequests();
        Http::fake([
            "$base/authenticated/eventjunction-images/*" => Http::response($this->png()),
            "$base/eventjunction-images/*" => Http::response([], 201),
            "$base/eventjunction-images" => Http::sequence()->pushStatus(503)->push([]),
        ]);
        $this->actingAs($owner, 'web');
        $image = $this->upload()->assertCreated()->json('id');
        $this->get('/assets/images/'.$image)->assertOk()->assertContent($this->png());
        $this->deleteJson('/assets/images/'.$image)->assertServiceUnavailable();
        $this->get('/assets/images/'.$image)->assertNotFound();
        $this->deleteJson('/assets/images/'.$image)->assertNoContent();
        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->body() === $this->png()
            && $request->hasHeader('Authorization', 'Bearer fixture-storage-key')
            && $request->hasHeader('Content-Type', 'image/png'));
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && $request->url() === "$base/eventjunction-images"
            && str_ends_with($request['prefixes'][0], '/'.$image));
        $this->assertSame([], Storage::disk('workspace_images')->allFiles());
    }

    private function upload(string $name = 'garden.png')
    {
        return $this->postJson('/assets/images', ['image' => UploadedFile::fake()->createWithContent($name, $this->png())]);
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAA8AAAAJCAIAAABi7eEAAAAAF0lEQVR4nGP0Lo9jIBowEa90VDUVVAMA36gBMpL5wXEAAAAASUVORK5CYII=');
    }
}
