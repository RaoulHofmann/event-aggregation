<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class GuardRegistration
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('register') && $request->isMethod('GET')) {
            $request->session()->put('registration_form_at', now()->timestamp);
        }

        if ($request->routeIs('register.store')) {
            $key = 'registration:'.hash('sha256', $request->ip() ?? 'unknown');
            abort_if(RateLimiter::increment($key, 600) > 12, 429, 'Too many registration attempts. Please try again in a few minutes.');

            $age = now()->timestamp - (int) $request->session()->get('registration_form_at', 0);
            if ($request->filled('company_website') || $age < 2 || $age > 7200) {
                throw ValidationException::withMessages(['registration_check' => 'Please reload the registration form and try again.']);
            }

            $siteKey = config('services.turnstile.site_key');
            $secret = config('services.turnstile.secret');
            if (filled($siteKey) || filled($secret)) {
                $token = $request->input('turnstile_token');
                if (! filled($siteKey) || ! filled($secret) || ! is_string($token) || $token === '' || strlen($token) > 2048 || ! $this->verifyTurnstile($token, $secret)) {
                    throw ValidationException::withMessages(['registration_check' => 'Complete the spam check and try again.']);
                }
            }
        }

        return $next($request);
    }

    private function verifyTurnstile(string $token, string $secret): bool
    {
        try {
            $response = Http::asForm()->withoutRedirecting()->connectTimeout(3)->timeout(6)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                ]);

            return $response->successful()
                && $response->json('success') === true
                && $response->json('action') === 'register'
                && $response->json('hostname') === parse_url(config('public-site.url'), PHP_URL_HOST);
        } catch (Throwable) {
            return false;
        }
    }
}
