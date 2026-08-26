<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>توثيق واجهة البرمجة — منصة المعرفة السعودية</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">

    {{-- Swagger UI from its official CDN. Kept out of the Vite bundle on
         purpose: this page is a developer tool, not part of the site. --}}
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #fafafa; }
        .topbar { display: none; }
        .doc-header {
            background: linear-gradient(135deg, #063f32 0%, #074D31 100%);
            color: #fff; padding: 18px 24px; display: flex; align-items: center; gap: 12px;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        .doc-header img { height: 34px; width: 34px; border-radius: 8px; background: #fff; padding: 3px; }
        .doc-header h1 { font-size: 16px; margin: 0; font-weight: 700; }
        .doc-header p { font-size: 12px; margin: 2px 0 0; opacity: .75; }
        .doc-header a { margin-inline-start: auto; color: #cfe7db; font-size: 12px; text-decoration: none; }
        .doc-header a:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="doc-header">
        <img src="{{ asset('images/logo.png') }}" alt="">
        <div>
            <h1>Saudi Knowledge Platform — REST API v1</h1>
            <p>Press <strong>Authorize</strong> and paste a Sanctum token from <code>/auth/login</code>.</p>
        </div>
        <a href="{{ route('home') }}">← العودة إلى الموقع</a>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js" crossorigin></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js" crossorigin></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: @json(route('api.openapi')),
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
                plugins: [SwaggerUIBundle.plugins.DownloadUrl],
                layout: 'StandaloneLayout',
                persistAuthorization: true,
                docExpansion: 'list',
                defaultModelsExpandDepth: 0
            });
        };
    </script>
</body>
</html>
