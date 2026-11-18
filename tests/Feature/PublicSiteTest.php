<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_metadata_and_sitemap_use_the_configured_origin(): void
    {
        config(['public-site.url' => 'https://example.test']);
        $this->get('/?ref=example')->assertOk()
            ->assertSee('<link rel="canonical" href="https://example.test/">', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('property="og:image" content="https://example.test/eventjunction-social.png"', false)
            ->assertDontSee('noindex, nofollow');
        $this->get('/login')->assertOk()->assertSee('noindex, nofollow')
            ->assertDontSee('rel="canonical"', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://example.test/sitemap.xml');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('https://example.test/privacy-policy')->assertSee('https://example.test/terms-of-service')
            ->assertDontSee('/designs/homepage')->assertDontSee('/admin');
    }

    public function test_legal_documents_are_available_to_guests_and_pending_users(): void
    {
        config(['public-site.contact_email' => 'privacy@example.test']);
        $this->get('/privacy-policy')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('PrivacyPolicy')->where('legal.email', 'privacy@example.test')
                ->where('policy', fn (string $html) => str_contains($html, 'Limited Use requirements'))
        );
        $this->get('/terms-of-service')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('TermsOfService')->has('terms')->has('legal.updated')
        );

        $pending = User::factory()->create();
        $pending->forceFill(['account_status' => User::PENDING])->save();
        $this->actingAs($pending)->get('/privacy-policy')->assertOk();
        $this->get('/terms-of-service')->assertOk();
    }
}
