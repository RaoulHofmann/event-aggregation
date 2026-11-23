<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @include('public-head')

        @php($tagManagerId = config('public-site.google_tag_manager_id'))
        @if ($tagManagerId && auth()->guest() && request()->routeIs(...array_keys(config('public-site.pages'))))
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
        @inertia
    </body>
</html>
