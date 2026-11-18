<?php

namespace App\Jobs;

use App\Mail\PendingRegistrationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Throwable;

class SendRegistrationAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $userId) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        if (blank(config('services.resend.key'))) {
            return;
        }

        $user = User::find($this->userId);
        if ($user?->account_status !== User::PENDING) {
            return;
        }

        $recipient = User::query()
            ->where('is_platform_admin', true)
            ->where('account_status', User::ACTIVE)
            ->orderBy('id')
            ->value('email');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Count delivery attempts, including retries, before contacting Resend.
        if (RateLimiter::increment('registration-alerts:delivery', 3600) > 10) {
            return;
        }

        try {
            Mail::mailer('resend')->to($recipient)->send(new PendingRegistrationMail($user->name, $user->email));
        } catch (Throwable) {
            throw new RuntimeException('Registration alert delivery failed. Check the Resend sender and API key.');
        }
    }
}
