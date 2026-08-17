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
        @include('partials.nav')

        <main class="flex-1">
            @if (session('success'))
                <div class="max-w-5xl mx-auto mt-4 px-4">
                    <div class="rounded-lg bg-brand-50 border border-brand-200 text-brand-700 px-4 py-3 text-sm">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-5xl mx-auto mt-4 px-4">
                    <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        @include('partials.footer')
    </body>
</html>
