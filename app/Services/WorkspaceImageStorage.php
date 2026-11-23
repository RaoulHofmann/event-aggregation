<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WorkspaceImageStorage
{
    public function put(string $key, string $bytes, string $mime): void
    {
        if (config('filesystems.workspace_images.remote')) {
            $response = $this->request()->withBody($bytes, $mime)
                ->withHeader('x-upsert', 'false')->post($this->url('/object/', $key));
            throw_unless($response->successful(), RuntimeException::class, 'Image storage could not save the upload.');

            return;
        }

        throw_unless(Storage::disk('workspace_images')->put($key, $bytes), RuntimeException::class, 'Image storage could not save the upload.');
    }

    public function get(string $key): ?string
    {
        if (config('filesystems.workspace_images.remote')) {
            $response = $this->request()->get($this->url('/object/authenticated/', $key));

            if ($response->notFound()) {
                return null;
            }
            throw_unless($response->successful(), RuntimeException::class, 'Image storage could not read the image.');

            return $response->body();
        }

        return Storage::disk('workspace_images')->get($key);
    }

    public function delete(string $key): void
    {
        if (config('filesystems.workspace_images.remote')) {
            $response = $this->request()->delete($this->url('/object/'), ['prefixes' => [$key]]);
            throw_unless($response->successful(), RuntimeException::class, 'Image storage could not delete the image.');

            return;
        }

        throw_unless(Storage::disk('workspace_images')->delete($key), RuntimeException::class, 'Image storage could not delete the image.');
    }

    private function request()
    {
        $key = config('filesystems.workspace_images.key');
        throw_unless(filled($key), RuntimeException::class, 'Image storage credentials are missing.');

        return Http::withoutRedirecting()->connectTimeout(4)->timeout(15)->withToken($key)->withHeader('apikey', $key);
    }

    private function url(string $prefix, string $key = ''): string
    {
        $origin = rtrim(config('filesystems.workspace_images.url') ?? '', '/');
        $bucket = config('filesystems.workspace_images.bucket');
        throw_unless(preg_match('~^https://[a-z0-9-]+\.supabase\.co$~D', $origin), RuntimeException::class, 'Image storage URL is invalid.');

        return $origin.'/storage/v1'.$prefix.rawurlencode($bucket).($key === '' ? '' : '/'.$key);
    }
}
