<?php

namespace App\Integrations;

use App\Models\ProviderConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Calendar requests are GET-only. POST is used solely at Google's OAuth token endpoint. */
class GoogleCalendarClient
{
    public const SCOPES = [
        'https://www.googleapis.com/auth/calendar.calendarlist.readonly',
        'https://www.googleapis.com/auth/calendar.events.readonly',
    ];

    public function configured(): bool
    {
        return filled(config('services.google_calendar.client_id')) && filled(config('services.google_calendar.client_secret')) && filled(config('services.google_calendar.redirect_uri'));
    }

    public function exchange(string $code, string $verifier): array
    {
        $tokens = $this->token(['grant_type' => 'authorization_code', 'code' => $code, 'code_verifier' => $verifier, 'redirect_uri' => config('services.google_calendar.redirect_uri')]);
        $granted = explode(' ', is_string($tokens['scope'] ?? null) ? $tokens['scope'] : '');
        if (array_diff(self::SCOPES, $granted)) {
            throw new GoogleCalendarException('Allow both read-only calendar permissions to connect Google.');
        }

        return $tokens;
    }

    private function token(array $data): array
    {
        if (! $this->configured()) {
            throw new GoogleCalendarException('Google Calendar is not configured on this installation yet.');
        }
        try {
            $response = Http::asForm()->acceptJson()->connectTimeout(10)->timeout(25)->withoutRedirecting()->post('https://oauth2.googleapis.com/token', $data + ['client_id' => config('services.google_calendar.client_id'), 'client_secret' => config('services.google_calendar.client_secret')]);
        } catch (Throwable) {
            throw new GoogleCalendarException('Google authorization could not be reached. Try again later.');
        }
        if (! $response->successful()) {
            if ($response->json('error') === 'invalid_grant') {
                throw new GoogleReconnectRequired('Google authorization has expired or was revoked. Reconnect this account.');
            }
            throw new GoogleCalendarException('Google authorization failed. Check the application configuration or reconnect.');
        }
        $payload = $response->json();
        if (! is_array($payload) || ! is_string($payload['access_token'] ?? null) || ! is_numeric($payload['expires_in'] ?? null) || (int) $payload['expires_in'] < 1) {
            throw new GoogleCalendarException('Google returned an unexpected authorization response. Reconnect the account.');
        }

        return array_filter(['access_token' => $payload['access_token'], 'refresh_token' => is_string($payload['refresh_token'] ?? null) ? $payload['refresh_token'] : null, 'expires_at' => now()->addSeconds((int) $payload['expires_in'])->timestamp, 'scope' => $payload['scope'] ?? null], fn ($v) => $v !== null);
    }

    private function accessToken(ProviderConnection $connection, bool $force = false): string
    {
        return DB::transaction(function () use ($connection, $force) {
            $active = ProviderConnection::where('provider', 'google_calendar')->whereKey($connection->id)->lockForUpdate()->firstOrFail();
            if (in_array($active->status, ['disconnected', 'reconnect_required'])) {
                throw new GoogleReconnectRequired('Reconnect Google before syncing.');
            }
            $credentials = $active->credentials ?? [];
            if (! $force && ($credentials['expires_at'] ?? 0) > now()->addMinute()->timestamp && filled($credentials['access_token'] ?? null)) {
                return $credentials['access_token'];
            }
            if (empty($credentials['refresh_token'])) {
                throw new GoogleReconnectRequired('Reconnect Google to restore calendar access.');
            }
            $tokens = $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $credentials['refresh_token']]);
            $active->update(['credentials' => array_replace($credentials, $tokens)]);

            return $tokens['access_token'];
        });
    }

    private function get(string $path, array $query, string $token): Response
    {
        try {
            return Http::withToken($token)->acceptJson()->connectTimeout(10)->timeout(25)->withoutRedirecting()->get('https://www.googleapis.com/calendar/v3/'.$path, $query);
        } catch (Throwable) {
            throw new GoogleCalendarException('Google Calendar could not be reached. Try again later.');
        }
    }

    private function request(ProviderConnection $connection, string $path, array $query = [], bool $allowMissing = false): ?array
    {
        $response = $this->get($path, $query, $this->accessToken($connection));
        if ($response->status() === 401) {
            $response = $this->get($path, $query, $this->accessToken($connection, true));
        }
        if ($allowMissing && in_array($response->status(), [404, 410])) {
            return null;
        }
        $this->check($response);

        return $response->json();
    }

    private function check(Response $response): void
    {
        if ($response->status() === 401) {
            throw new GoogleReconnectRequired('Google access was rejected. Reconnect this account.');
        }
        if (! $response->successful() || ! is_array($response->json())) {
            throw new GoogleCalendarException(match ($response->status()) {
                401 => 'Google access was rejected. Reconnect this account.',
                403, 404 => 'Google calendar access was denied. Check permissions and selected calendars.',
                429 => 'Google Calendar rate limit reached. Try again later.',
                default => 'Google Calendar could not complete the request. Try again later.',
            });
        }
    }

    /** OAuth callback can inspect calendars before credentials are persisted. */
    public function calendarsWithToken(string $token): array
    {
        return $this->calendarPages(function ($query) use ($token) {
            $response = $this->get('users/me/calendarList', $query, $token);
            $this->check($response);

            return $response->json();
        });
    }

    public function calendars(ProviderConnection $connection): array
    {
        return $this->calendarPages(fn ($query) => $this->request($connection, 'users/me/calendarList', $query));
    }

    private function calendarPages(callable $fetch): array
    {
        $calendars = [];
        foreach ($this->pages($fetch, ['maxResults' => 250, 'minAccessRole' => 'reader']) as $item) {
            if (! is_string($item['id'] ?? null) || ! in_array($item['accessRole'] ?? null, ['reader', 'writer', 'owner'])) {
                continue;
            }
            $calendars[] = ['id' => $item['id'], 'name' => mb_substr(strip_tags($item['summary'] ?? $item['id']), 0, 255), 'timezone' => $this->timezone($item['timeZone'] ?? null), 'primary' => (bool) ($item['primary'] ?? false)];
        }

        return $calendars;
    }

    private function pages(callable $fetch, array $query): \Generator
    {
        $seen = [];
        for ($page = 0; $page < 100; $page++) {
            $data = $fetch($query);
            if (! is_array($data['items'] ?? null)) {
                throw new GoogleCalendarException('Google returned an unexpected calendar page. Retry the sync.');
            }
            foreach ($data['items'] as $item) {
                if (! is_array($item)) {
                    throw new GoogleCalendarException('Google returned an unexpected calendar item. Retry the sync.');
                }
                yield $item;
            }
            $next = $data['nextPageToken'] ?? null;
            if ($next === null) {
                return;
            }
            if (! is_string($next) || $next === '' || isset($seen[$next])) {
                throw new GoogleCalendarException('Google returned incomplete calendar pagination. Retry the sync.');
            }
            $seen[$next] = true;
            $query['pageToken'] = $next;
        }
        throw new GoogleCalendarException('This calendar exceeds the current import limit.');
    }

    public function events(ProviderConnection $connection, string $calendar, string $from, string $to): \Generator
    {
        return $this->pages(fn ($query) => $this->request($connection, 'calendars/'.rawurlencode($calendar).'/events', $query), ['singleEvents' => 'true', 'showDeleted' => 'true', 'maxResults' => 250, 'timeMin' => $from, 'timeMax' => $to]);
    }

    public function event(ProviderConnection $connection, string $calendar, string $id): ?array
    {
        return $this->request($connection, 'calendars/'.rawurlencode($calendar).'/events/'.rawurlencode($id), [], true);
    }

    public function timezone(?string $zone): string
    {
        return in_array($zone, timezone_identifiers_list(\DateTimeZone::ALL_WITH_BC)) || $zone === 'UTC' ? $zone : 'UTC';
    }
}
