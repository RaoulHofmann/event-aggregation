<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_tag_manager_is_limited_to_signed_out_public_pages(): void
    {
        $this->get('/')->assertOk()->assertDontSee('googletagmanager.com/gtm.js', false);
        config(['public-site.google_tag_manager_id' => 'GTM-T2VCZSNS']);
        $tag = 'https://www.googletagmanager.com/gtm.js?id=GTM-T2VCZSNS';
        $this->get('/')->assertOk()->assertSeeInOrder(["'gtm.start'", $tag], false);
        $this->get('/pricing')->assertOk()->assertSee($tag, false);
        $this->get('/login')->assertOk()->assertDontSee($tag, false);
        $this->get('/embed/upcoming/example')->assertOk()->assertDontSee($tag, false);
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertDontSee($tag, false);
    }

    public function test_public_metadata_and_sitemap_use_the_configured_origin(): void
    {
        config(['public-site.url' => 'https://example.test']);
        $this->get('/?ref=example')->assertOk()
            ->assertSee('<link rel="canonical" href="https://example.test/">', false)
            ->assertSee('<title inertia>EventJunction — Event Calendar and Management Software</title>', false)
            ->assertSee('<meta name="description" content="Event calendar software for teams.', false)
            ->assertSee('<meta property="og:description" content="Event calendar software for teams.', false)
            ->assertSee('<meta name="twitter:description" content="Event calendar software for teams.', false)
            ->assertSee('property="og:image" content="https://example.test/eventjunction-social.png"', false)
            ->assertDontSee('noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page->where('pageTitle', config('public-site.pages.home.title')));
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
                ->where('legal.updated', config('public-site.privacy_updated'))
                ->where('policy', fn (string $html) => str_contains($html, 'Limited Use requirements'))
                ->where('policy', fn (string $html) => str_contains($html, 'How we protect sensitive data'))
                ->where('policy', fn (string $html) => str_contains($html, 'calendar.calendarlist.readonly')
                    && str_contains($html, 'calendar.events.readonly')
                    && str_contains($html, 'How Google data is shared')
                    && str_contains($html, 'Request permanent deletion of Google data'))
        );
        $this->get('/terms-of-service')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('TermsOfService')->has('terms')->where('legal.updated', config('public-site.legal_updated'))
        );

        $pending = User::factory()->create();
        $pending->forceFill(['account_status' => User::PENDING])->save();
        $this->actingAs($pending)->get('/privacy-policy')->assertOk();
        $this->get('/terms-of-service')->assertOk();
    }

    public function test_homepage_includes_valid_structured_application_metadata(): void
    {
        config(['public-site.url' => 'https://example.test']);
        $html = $this->get('/?ref=example')->assertOk()->getContent();
        $this->assertSame(1, preg_match('~<script type="application/ld\+json">(.+?)</script>~s', $html, $matches));
        $data = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('WebApplication', $data['@type']);
        $this->assertSame('https://example.test/#application', $data['@id']);
        $this->assertSame('https://example.test/', $data['url']);
        $this->assertSame(config('public-site.pages.home.description'), $data['description']);
        $this->assertSame(0, $data['offers']['price']);
        $this->get('/pricing')->assertOk()->assertDontSee('application/ld+json', false);
    }

    public function test_swagger_reference_lists_public_read_and_write_endpoints(): void
    {
        $this->get(route('l5-swagger.default.api', absolute: false))
            ->assertOk()
            ->assertSee('API reference', false)
            ->assertSee('href="/docs/api"', false)
            ->assertSee('persistAuthorization: false', false);

        $specification = $this->getJson(route('l5-swagger.default.docs', absolute: false))->assertOk()->json();
        $this->assertEqualsCanonicalizing([
            '/api/v1/events', '/api/v1/occurrences', '/api/events', '/api/event-templates',
            '/api/field-templates', '/api/user', '/api/v1/events/{id}',
            '/api/v1/event-templates', '/api/v1/event-templates/{id}',
            '/api/v1/field-templates', '/api/v1/field-templates/{id}',
        ], array_keys($specification['paths']));
        $this->assertEqualsCanonicalizing(['get', 'post'], array_keys($specification['paths']['/api/v1/events']));
        $this->assertEqualsCanonicalizing(['get', 'put'], array_keys($specification['paths']['/api/v1/events/{id}']));
        $this->assertArrayHasKey('requestBody', $specification['paths']['/api/v1/event-templates']['post']);
        $this->assertSame('bearer', $specification['components']['securitySchemes']['apiKey']['scheme']);
        $this->assertTrue($specification['paths']['/api/v1/occurrences']['get']['parameters'][0]['required']);
    }
}
