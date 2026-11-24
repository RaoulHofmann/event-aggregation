<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @include('public-head')

        @php
            $tagManagerId = config('public-site.google_tag_manager_id');
            $isPublicPage = isset($publicMetadata) || request()->routeIs(...array_keys(config('public-site.pages')));
        @endphp
        @if ($tagManagerId && auth()->guest() && $isPublicPage)
            <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
                function gtag() { window.dataLayer.push(arguments); }
                gtag('js', new Date());
            </script>
            <script async src="https://www.googletagmanager.com/gtm.js?id={{ $tagManagerId }}"></script>
        @endif

        <link rel="icon" type="image/svg+xml" href="{{ asset('eventjunction.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @php
            $legal = $page['props']['policy'] ?? $page['props']['terms'] ?? null;
            $prerender = storage_path('app/public-pages/'.str_replace('/', '-', $page['component']).'.html');
        @endphp
        @if ($isPublicPage && isset($page['props']['blogHtml']))
            <div id="app" data-page="{{ json_encode($page) }}">
                @if (auth()->guest() && ! app(\Illuminate\Foundation\Vite::class)->isRunningHot() && is_file($prerender))
                    {!! file_get_contents($prerender) !!}
                @endif
                <div>{!! $page['props']['blogHtml'] !!}</div>
            </div>
        @elseif ($isPublicPage && $legal)
            <div id="app" data-page="{{ json_encode($page) }}">
                <main class="prose mx-auto max-w-3xl p-6">
                    <a href="/">EventJunction home</a>
                    {!! $legal !!}
                </main>
            </div>
        @elseif ($isPublicPage && auth()->guest() && ! app(\Illuminate\Foundation\Vite::class)->isRunningHot() && is_file($prerender))
            <div id="app" data-page="{{ json_encode($page) }}">{!! file_get_contents($prerender) !!}</div>
        @else
            @inertia
        @endif
    </body>
</html>
