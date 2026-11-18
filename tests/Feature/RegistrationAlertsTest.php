<?php

namespace Tests\Feature;

use App\Jobs\SendRegistrationAlert;
use App\Mail\PendingRegistrationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegistrationAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.resend.key' => 'test-only-resend-key',
            'public-site.url' => 'https://eventjunction.app',
        ]);
    }

    private function registration(array $extra = []): array
    {
        return $extra + [
            'name' => 'New organizer',
            'email' => 'organizer@example.test',
            'password' => 'test-password',
            'password_confirmation' => 'test-password',
            'terms' => true,
        ];
    }

    public function test_new_pending_registration_queues_one_admin_alert(): void
    {
        Queue::fake();
        $this->get('/register')->assertOk();
        $this->travel(3)->seconds();
        $this->post('/register', $this->registration())->assertRedirect(route('account.status'));

        $applicant = User::where('email', 'organizer@example.test')->firstOrFail();
        Queue::assertPushedOnce(SendRegistrationAlert::class);
        Queue::assertPushed(SendRegistrationAlert::class, fn ($job) => $job->userId === $applicant->id);
    }

    public function test_direct_and_honeypot_submissions_do_not_create_accounts_or_queue_mail(): void
    {
        Queue::fake();
        $this->post('/register', $this->registration())->assertSessionHasErrors('registration_check');
        $this->withSession(['registration_form_at' => now()->subSeconds(3)->timestamp])
            ->post('/register', $this->registration(['company_website' => 'https://bot.example.test']))
            ->assertSessionHasErrors('registration_check');
        $this->assertDatabaseCount('users', 0);
        Queue::assertNothingPushed();
    }

    public function test_registration_rate_limiter_counts_blocked_attempts(): void
    {
        Queue::fake();
        for ($attempt = 0; $attempt < 12; $attempt++) {
            $this->postJson('/register', $this->registration())->assertUnprocessable();
        }
        $this->postJson('/register', $this->registration())->assertTooManyRequests();
        $this->assertDatabaseCount('users', 0);
        Queue::assertNothingPushed();
    }

    public function test_turnstile_checks_hostname_and_action_before_queueing(): void
    {
        Queue::fake();
        config(['services.turnstile.site_key' => 'test-site-key', 'services.turnstile.secret' => 'test-secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
            ->push(['success' => true, 'hostname' => 'other.example.test', 'action' => 'register'])
            ->push(['success' => true, 'hostname' => 'eventjunction.app', 'action' => 'login'])
            ->push(['success' => true, 'hostname' => 'eventjunction.app', 'action' => 'register'])]);
        $this->withSession(['registration_form_at' => now()->subSeconds(3)->timestamp]);
        $this->post('/register', $this->registration())->assertSessionHasErrors('registration_check');
        $this->post('/register', $this->registration(['turnstile_token' => 'wrong-host']))->assertSessionHasErrors('registration_check');
        $this->post('/register', $this->registration(['turnstile_token' => 'wrong-action']))->assertSessionHasErrors('registration_check');
        $this->assertDatabaseCount('users', 0);
        Queue::assertNothingPushed();
        $this->post('/register', $this->registration(['turnstile_token' => 'valid-token']))->assertRedirect(route('account.status'));
        Queue::assertPushedOnce(SendRegistrationAlert::class);
    }

    public function test_alert_job_respects_delivery_budget_and_only_emails_pending_accounts(): void
    {
        Mail::fake();
        $applicant = User::factory()->create();
        (new SendRegistrationAlert($applicant->id))->handle();
        Mail::assertNothingSent();

        $applicant->forceFill(['account_status' => User::PENDING])->save();
        (new SendRegistrationAlert($applicant->id))->handle();
        Mail::assertNothingSent();

        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true, 'email' => 'operator@example.test'])->save();
        RateLimiter::increment('registration-alerts:delivery', 3600, 9);
        (new SendRegistrationAlert($applicant->id))->handle();
        (new SendRegistrationAlert($applicant->id))->handle();

        Mail::assertSentCount(1);
        Mail::assertSent(PendingRegistrationMail::class, fn ($mail) => $mail->hasTo('operator@example.test')
            && $mail->applicantEmail === $applicant->email
            && $mail->content()->with['reviewUrl'] === 'https://eventjunction.app/admin?status=pending');
    }
}
