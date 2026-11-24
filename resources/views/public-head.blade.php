@php
    $pageRoute = request()->route()?->getName();
    $metadata = $publicMetadata ?? config('public-site.pages')[$pageRoute] ?? null;
@endphp
<title inertia>{{ $metadata['title'] ?? config('app.name') }}</title>
@if ($metadata)
    @php
        $canonicalUrl = \App\Support\PublicSite::url($canonicalPath ?? route($pageRoute, absolute: false));
        $socialImage = \App\Support\PublicSite::url('eventjunction-social.png');
    @endphp
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="description" content="{{ $metadata['description'] }}">
    <meta property="og:type" content="{{ $metadata['type'] ?? 'website' }}">
    <meta property="og:site_name" content="EventJunction">
    <meta property="og:title" content="{{ $metadata['title'] }}">
    <meta property="og:description" content="{{ $metadata['description'] }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $socialImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="EventJunction. All your events. One place.">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metadata['title'] }}">
    <meta name="twitter:description" content="{{ $metadata['description'] }}">
    <meta name="twitter:image" content="{{ $socialImage }}">
    <meta name="twitter:image:alt" content="EventJunction. All your events. One place.">
    @if ($pageRoute === 'home')
        <script type="application/ld+json">{!! \Illuminate\Support\Js::encode(\App\Support\PublicSite::webApplication()) !!}</script>
        <script type="application/ld+json">{!! \Illuminate\Support\Js::encode(\App\Support\PublicSite::website()) !!}</script>
    @endif
@else
    <meta name="robots" content="noindex, nofollow">
@endif
