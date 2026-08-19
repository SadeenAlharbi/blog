<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'منصة المعرفة السعودية')</title>

        @fonts
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ink-25 text-ink-800 antialiased">
        {{-- Standalone auth page: no site header/footer, just a centered card. --}}
        <main class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
            <div class="w-full max-w-md">
                @if (session('success'))
                    <div class="mb-5"><x-alert type="success">{{ session('success') }}</x-alert></div>
                @endif
                @if (session('error'))
                    <div class="mb-5"><x-alert type="error">{{ session('error') }}</x-alert></div>
                @endif
                @if (session('status'))
                    <div class="mb-5"><x-alert type="success">{{ session('status') }}</x-alert></div>
                @endif

                @yield('content')

                <p class="text-center mt-8">
                    <a href="{{ url('/') }}" class="text-xs text-ink-400 hover:text-brand-600 transition-colors">← العودة إلى الرئيسية</a>
                </p>
            </div>
        </main>
    </body>
</html>
