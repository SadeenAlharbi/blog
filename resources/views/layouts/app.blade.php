<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'منصة المعرفة السعودية')</title>
        <meta name="description" content="@yield('description', 'منصة معرفية سعودية تغطي التاريخ، رؤية 2030، الاقتصاد، التقنية، الذكاء الاصطناعي، الثقافة والسياحة.')">

        @fonts
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ink-25 text-ink-800 antialiased flex flex-col">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:start-3 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:text-white">
            تخطَّ إلى المحتوى
        </a>

        @include('partials.nav')

        <main id="main-content" class="flex-1">
            @if (session('success'))
                <div class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
                    <x-alert type="success">{{ session('success') }}</x-alert>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
                    <x-alert type="error">{{ session('error') }}</x-alert>
                </div>
            @endif

            @yield('content')
        </main>

        @include('partials.footer')
    </body>
</html>
