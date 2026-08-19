<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-ink-100">
    {{-- Saudi-green accent line --}}
    <div class="h-1 w-full bg-gradient-to-l from-[#074D31] via-brand-500 to-brand-600"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 shrink-0">
            <x-logo :size="40" />
            <span class="font-bold text-ink-900 leading-tight hidden sm:block">منصة المعرفة السعودية</span>
        </a>

        {{--
            Deliberately a <div role="navigation"> and not a <nav>: Platforms Code's bundled
            core.css ships an unscoped, unlayered `nav,header,footer,section{display:block}` reset,
            which (being unlayered) beats Tailwind's layered `hidden`/`md:flex`. A <div> isn't targeted.
        --}}
        <div role="navigation" aria-label="التنقل الرئيسي" class="hidden md:flex items-center gap-7 text-sm font-medium text-ink-600">
            <a href="{{ url('/') }}" class="hover:text-brand-600 transition-colors {{ request()->is('/') ? 'text-brand-600' : '' }}">الرئيسية</a>
            <a href="{{ route('posts.index') }}" class="hover:text-brand-600 transition-colors {{ request()->routeIs('posts.*') ? 'text-brand-600' : '' }}">المقالات</a>
            @auth
                <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors {{ request()->routeIs('dashboard*') ? 'text-brand-600' : '' }}">الصفحة الشخصية</a>
            @endauth
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <a href="{{ route('posts.index') }}" aria-label="بحث" title="بحث"
               class="hidden sm:inline-flex items-center justify-center h-10 w-10 rounded-lg text-ink-500 hover:bg-ink-50 hover:text-brand-600 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </a>

            @auth
                <span class="hidden lg:inline text-sm text-ink-500">مرحباً، {{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-ink-600 hover:text-red-600 transition-colors">تسجيل الخروج</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium text-ink-600 hover:text-brand-600 transition-colors">تسجيل الدخول</a>
                <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-semibold hover:bg-brand-700 transition-colors">إنشاء حساب</a>
            @endauth

            <button
                type="button"
                id="mobile-menu-toggle"
                class="md:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg text-ink-600 hover:bg-ink-50 transition-colors"
                aria-controls="mobile-menu"
                aria-expanded="false"
                aria-label="فتح القائمة"
            >
                <svg id="mobile-menu-icon-open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
                <svg id="mobile-menu-icon-close" class="hidden h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div id="mobile-menu" role="navigation" aria-label="القائمة" class="hidden md:hidden border-t border-ink-100 bg-white px-4 py-3 space-y-1 text-sm font-medium text-ink-600">
        <a href="{{ url('/') }}" class="block rounded-lg px-3 py-2 hover:bg-ink-50 hover:text-brand-600 transition-colors {{ request()->is('/') ? 'text-brand-600' : '' }}">الرئيسية</a>
        <a href="{{ route('posts.index') }}" class="block rounded-lg px-3 py-2 hover:bg-ink-50 hover:text-brand-600 transition-colors {{ request()->routeIs('posts.*') ? 'text-brand-600' : '' }}">المقالات</a>
        @auth
            <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-ink-50 hover:text-brand-600 transition-colors {{ request()->routeIs('dashboard*') ? 'text-brand-600' : '' }}">الصفحة الشخصية</a>
            <div class="my-2 border-t border-ink-100"></div>
            <span class="block px-3 py-1 text-xs text-ink-400">مرحباً، {{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-start rounded-lg px-3 py-2 hover:bg-ink-50 hover:text-red-600 transition-colors">تسجيل الخروج</button>
            </form>
        @else
            <div class="my-2 border-t border-ink-100"></div>
            <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 hover:bg-ink-50 hover:text-brand-600 transition-colors">تسجيل الدخول</a>
            <a href="{{ route('register') }}" class="block rounded-lg px-3 py-2 bg-brand-600 text-white text-center font-semibold hover:bg-brand-700 transition-colors">إنشاء حساب</a>
        @endauth
    </div>
</header>

<script>
    (function () {
        var toggle = document.getElementById('mobile-menu-toggle');
        var menu = document.getElementById('mobile-menu');
        var iconOpen = document.getElementById('mobile-menu-icon-open');
        var iconClose = document.getElementById('mobile-menu-icon-close');
        if (!toggle || !menu) return;

        function setOpen(isOpen) {
            menu.classList.toggle('hidden', !isOpen);
            iconOpen.classList.toggle('hidden', isOpen);
            iconClose.classList.toggle('hidden', !isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
            toggle.setAttribute('aria-label', isOpen ? 'إغلاق القائمة' : 'فتح القائمة');
        }

        toggle.addEventListener('click', function () {
            setOpen(menu.classList.contains('hidden'));
        });

        menu.querySelectorAll('a, button').forEach(function (el) {
            el.addEventListener('click', function () { setOpen(false); });
        });
    })();
</script>
