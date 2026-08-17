<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Platforms Code — Component Test</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 p-8">
        <h1 class="text-xl font-semibold mb-4">Platforms Code UI — integration check</h1>
        <p class="text-sm text-gray-600 mb-6">
            This route verifies that <code>platformscode-new-react</code> components mount correctly via Vite/React.
            Real usage is integrated into the platform's dashboard, search, and filtering UI (see Phase 16).
        </p>
        <div id="platformscode-test-root"></div>
    </body>
</html>
