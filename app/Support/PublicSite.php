<?php

namespace App\Support;

class PublicSite
{
    public static function url(string $path = ''): string
    {
        return rtrim(config('public-site.url'), '/').'/'.ltrim($path, '/');
    }
}
