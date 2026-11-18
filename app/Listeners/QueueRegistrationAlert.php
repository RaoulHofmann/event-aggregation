<?php

namespace App\Listeners;

use App\Jobs\SendRegistrationAlert;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Throwable;

class QueueRegistrationAlert
{
    public function handle(Registered $event): void
    {
        $user = $event->user;
        if (! $user instanceof User || $user->account_status !== User::PENDING || blank(config('services.resend.key'))) {
            return;
        }

        try {
            SendRegistrationAlert::dispatch($user->id)->afterCommit();
        } catch (Throwable) {
            Log::warning('Could not queue a registration alert. The registration is still available for review.', ['user_id' => $user->id]);
        }
    }
}
