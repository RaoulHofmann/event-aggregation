<?php

namespace App\Integrations\Inbound;

use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/** Fetch owner-supplied feed addresses without allowing access to private networks. */
class PublicFeedClient
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    public function get(string $url, array $query = []): string
    {
        $host = $this->hostname($url);
        $addresses = $this->addresses($host);
        if (! $addresses || collect($addresses)->contains(fn ($address) => filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false)) {
            throw new ImportException('Use a public HTTPS feed or WordPress address.');
        }
        if (! extension_loaded('curl')) {
            throw new ImportException('Secure feed downloads are unavailable on this installation.');
        }
        $address = $addresses[0];
        $pin = str_contains($address, ':') ? "[{$address}]" : $address;
        $peer = null;
        try {
            $request = Http::withoutRedirecting()->connectTimeout(5)->timeout(12)
                ->withHeaders(['Accept-Encoding' => 'identity'])
                ->withOptions([
                    'proxy' => '',
                    'curl' => [CURLOPT_RESOLVE => ["{$host}:443:{$pin}"], CURLOPT_FRESH_CONNECT => true],
                    'decode_content' => false,
                    'on_stats' => function (TransferStats $stats) use (&$peer) {
                        $peer = $stats->getHandlerStat('primary_ip');
                    },
                    'on_headers' => function (ResponseInterface $response) {
                        if ((int) $response->getHeaderLine('Content-Length') > self::MAX_BYTES) {
                            throw new ImportException('The source response is too large.');
                        }
                    },
                    'progress' => function ($total, $downloaded) {
                        if ($total > self::MAX_BYTES || $downloaded > self::MAX_BYTES) {
                            throw new ImportException('The source response is too large.');
                        }
                    },
                ]);
            $response = $query === [] ? $request->get($url) : $request->get($url, $query);
        } catch (Throwable) {
            throw new ImportException('The source could not be downloaded. Check its address, permissions and size.');
        }
        if ($peer !== null && inet_pton($peer) !== inet_pton($address)) {
            throw new ImportException('The source resolved to an unexpected address.');
        }
        if (! $response->successful() || strlen($response->body()) > self::MAX_BYTES || ! in_array(strtolower($response->header('Content-Encoding') ?? ''), ['', 'identity'], true)) {
            throw new ImportException('The source did not return a readable document. Use its final HTTPS address.');
        }

        return $response->body();
    }

    public function hostname(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (! filter_var($url, FILTER_VALIDATE_URL) || ($parts['scheme'] ?? null) !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || ($parts['port'] ?? 443) !== 443 || filter_var($host, FILTER_VALIDATE_IP)
            || ! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,62}$/D', $host)) {
            throw new ImportException('Enter a public HTTPS URL without user credentials or a fragment.');
        }

        return $host;
    }

    protected function addresses(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        return array_values(array_filter(array_map(fn ($row) => $row['ip'] ?? $row['ipv6'] ?? null, $records)));
    }
}
