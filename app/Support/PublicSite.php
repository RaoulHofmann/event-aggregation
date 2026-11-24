<?php

namespace App\Support;

class PublicSite
{
    public static function url(string $path = ''): string
    {
        return rtrim(config('public-site.url'), '/').'/'.ltrim($path, '/');
    }

    public static function webApplication(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            '@id' => self::url().'#application',
            'name' => 'EventJunction',
            'url' => self::url(),
            'description' => config('public-site.pages.home.description'),
            'image' => self::url('eventjunction-social.png'),
            'applicationCategory' => 'BusinessApplication',
            'isAccessibleForFree' => true,
            'offers' => ['@type' => 'Offer', 'price' => 0, 'url' => self::url('pricing')],
        ];
    }

    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => self::url().'#website',
            'name' => 'EventJunction',
            'url' => self::url(),
        ];
    }
}
