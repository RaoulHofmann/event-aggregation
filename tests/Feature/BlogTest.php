<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_platform_administrators_can_manage_posts(): void
    {
        $this->get('/admin/blog')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get('/admin/blog')->assertForbidden();
        $this->get('/admin/blog/create')->assertForbidden();
        $this->postJson('/admin/blog', $this->postFields())->assertForbidden();
        $this->assertDatabaseCount('public.blog_posts', 0);
    }

    public function test_posts_can_be_drafted_published_edited_and_unpublished(): void
    {
        config(['public-site.url' => 'https://eventjunction.app']);
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $data = $this->postFields();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('admin.blog.index');
        $this->get('/admin/blog')->assertOk();
        $this->postJson('/admin/blog', ['title' => 'Missing details'])->assertUnprocessable()->assertJsonValidationErrors(['summary', 'body', 'status']);
        $this->post('/admin/blog', $data)->assertRedirect();
        $post = BlogPost::sole();
        $path = '/blog/'.$post->slug;
        $this->assertSame('calendar-updates', $post->slug);
        $this->assertNull($post->published_at);
        $this->get($path)->assertNotFound();
        $this->get('/blog')->assertOk()->assertDontSee('Calendar updates');
        $this->get('/sitemap.xml')->assertOk()->assertDontSee($path);

        $updated = array_replace($data, ['status' => 'published']);
        $this->put('/admin/blog/'.$post->id, $updated)->assertRedirect(route('admin.blog.edit', $post));
        $publishedAt = $post->fresh()->published_at;
        $this->app['auth']->forgetGuards();
        $this->get('/blog')->assertOk()->assertSee('href="'.$path.'"', false);
        $this->get($path)->assertOk()
            ->assertSee('<link rel="canonical" href="https://eventjunction.app'.$path.'">', false)
            ->assertSee('<meta name="description" content="Our monthly changes.">', false)
            ->assertSee('<h2>What changed</h2>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="javascript:', false)
            ->assertDontSee('noindex, nofollow');
        $this->get('/sitemap.xml')->assertOk()->assertSee('https://eventjunction.app'.$path);

        $this->travel(1)->days();
        $this->actingAs($admin, 'web')
            ->put('/admin/blog/'.$post->id, array_replace($updated, ['title' => 'New title']))->assertRedirect();
        $this->assertSame($post->slug, $post->fresh()->slug);
        $this->assertTrue($post->fresh()->published_at->equalTo($publishedAt));
        $this->get($path)->assertOk()->assertSee('New title');

        $this->post('/admin/blog', $data)->assertRedirect();
        $this->assertDatabaseHas('public.blog_posts', ['slug' => 'calendar-updates-2', 'published_at' => null]);
        $this->put('/admin/blog/'.$post->id, $data)->assertRedirect();
        $this->get($path)->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertDontSee($path);
    }

    private function postFields(): array
    {
        return [
            'title' => 'Calendar updates',
            'summary' => 'Our monthly changes.',
            'body' => "## What changed\n\nThis is a fixture.\n\n<script>alert(1)</script>\n\n[Unsafe link](javascript:alert%281%29)",
            'status' => 'draft',
        ];
    }
}
