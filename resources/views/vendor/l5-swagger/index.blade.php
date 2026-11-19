<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('l5-swagger.documentations.'.$documentation.'.api.title') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('eventjunction.svg') }}">
    <link rel="stylesheet" href="{{ l5_swagger_asset($documentation, 'swagger-ui.css') }}">
    <style>
        body { margin: 0; background: #faf8f3; }
        .reference-bar { background: #fff0dc; border-bottom: 1px solid #dfcfb6; color: #27231f; font: 16px/1.5 Arial, sans-serif; }
        .reference-bar nav { box-sizing: border-box; max-width: 1460px; margin: auto; padding: 16px 20px; display: flex; flex-wrap: wrap; align-items: center; gap: 12px 24px; }
        .reference-bar a { color: inherit; text-underline-offset: 3px; }
        .reference-bar .brand { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; text-decoration: none; }
        .reference-bar .brand img { width: 28px; height: 28px; }
        a:focus-visible { outline: 2px solid #80572f; outline-offset: 4px; }
    </style>
</head>
<body>
    <header class="reference-bar">
        <nav aria-label="API documentation">
            <a class="brand" href="{{ route('home', absolute: false) }}">
                <img src="{{ asset('eventjunction.svg') }}" alt="">
                EventJunction
            </a>
            <a href="{{ route('docs.api', absolute: false) }}">API guide</a>
            <a href="{{ $urlToDocs }}">OpenAPI specification</a>
        </nav>
    </header>
    <main id="swagger-ui"></main>
    <script src="{{ l5_swagger_asset($documentation, 'swagger-ui-bundle.js') }}"></script>
    <script>
        SwaggerUIBundle({
            dom_id: '#swagger-ui',
            url: {{ \Illuminate\Support\Js::from($urlToDocs) }},
            validatorUrl: null,
            persistAuthorization: false,
            supportedSubmitMethods: ['get', 'post', 'put'],
            requestInterceptor: request => ({ ...request, credentials: 'omit' }),
            presets: [SwaggerUIBundle.presets.apis],
            deepLinking: true,
            filter: true,
            docExpansion: 'list',
        });
    </script>
</body>
</html>
