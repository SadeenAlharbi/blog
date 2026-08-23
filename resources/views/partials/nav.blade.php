@php
    // Notification bell data (authenticated users only). Uses Laravel's built-in
    // database notifications on the current user (Notifiable trait).
    $navUnreadCount = 0;
    $navRecent = collect();
    if (auth()->check()) {
        $navUnreadCount = auth()->user()->unreadNotifications()->count();
        $navRecent = auth()->user()->notifications()->latest()->take(8)->get();
    }
@endphp
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
                {{-- ===================== Notification bell ===================== --}}
                <div class="relative" id="notif-bell">
                    <button type="button" id="notif-toggle"
                            class="relative inline-flex items-center justify-center h-10 w-10 rounded-lg text-ink-500 hover:bg-ink-50 hover:text-brand-600 transition-colors"
                            aria-haspopup="true" aria-expanded="false" aria-label="الإشعارات">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        @if ($navUnreadCount > 0)
                            <span class="absolute -top-0.5 -end-0.5 min-w-[18px] h-[18px] px-1 inline-flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold leading-none ring-2 ring-white">
                                {{ $navUnreadCount > 9 ? '9+' : $navUnreadCount }}
                            </span>
                        @endif
                    </button>

                    <div id="notif-dropdown"
                         class="hidden absolute end-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-2xl border border-ink-100 shadow-xl shadow-ink-900/10 overflow-hidden z-40">
                        <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-ink-100">
                            <span class="text-sm font-bold text-ink-900">الإشعارات</span>
                            @if ($navUnreadCount > 0)
                                <form method="POST" action="{{ route('notifications.readAll') }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-brand-600 hover:text-brand-700 transition-colors">تعليم الكل كمقروء</button>
                                </form>
                            @endif
                        </div>

                        <div class="max-h-96 overflow-y-auto divide-y divide-ink-50">
                            @forelse ($navRecent as $note)
                                @php $d = $note->data; $unread = is_null($note->read_at); @endphp
                                <form method="POST" action="{{ route('notifications.read', $note->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-start px-4 py-3 flex gap-3 transition-colors hover:bg-ink-50 {{ $unread ? 'bg-brand-50/60' : '' }}">
                                        <span class="mt-1 shrink-0">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-brand-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                                            </span>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-xs text-ink-700 leading-relaxed">
                                                <span class="font-semibold text-ink-900">{{ $d['commenter_name'] ?? 'مستخدم' }}</span>
                                                علّق على مقالك
                                                <span class="font-semibold text-brand-700">«{{ \Illuminate\Support\Str::limit($d['post_title'] ?? '', 40) }}»</span>
                                            </span>
                                            @if (!empty($d['excerpt']))
                                                <span class="block text-xs text-ink-400 mt-0.5 line-clamp-1">{{ $d['excerpt'] }}</span>
                                            @endif
                                            <span class="block text-[11px] text-ink-300 mt-1">{{ $note->created_at->diffForHumans() }}</span>
                                        </span>
                                        @if ($unread)
                                            <span class="mt-1 shrink-0 h-2 w-2 rounded-full bg-brand-500" aria-label="غير مقروء"></span>
                                        @endif
                                    </button>
                                </form>
                            @empty
                                <div class="px-4 py-10 text-center">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-ink-50 text-ink-300">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                    </div>
                                    <p class="text-sm text-ink-500">لا توجد إشعارات بعد.</p>
                                </div>
                            @endforelse
                        </div>

                        <a href="{{ route('notifications.index') }}"
                           class="block text-center px-4 py-3 text-xs font-semibold text-brand-600 hover:bg-ink-50 border-t border-ink-100 transition-colors">
                            عرض كل الإشعارات
                        </a>
                    </div>
                </div>

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
            <a href="{{ route('notifications.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-ink-50 hover:text-brand-600 transition-colors {{ request()->routeIs('notifications.*') ? 'text-brand-600' : '' }}">
                <span>الإشعارات</span>
                @if ($navUnreadCount > 0)
                    <span class="min-w-[18px] h-[18px] px-1 inline-flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold leading-none">{{ $navUnreadCount > 9 ? '9+' : $navUnreadCount }}</span>
                @endif
            </a>
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
        if (toggle && menu) {
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
        }

        // Notification bell dropdown.
        var bell = document.getElementById('notif-bell');
        var bellToggle = document.getElementById('notif-toggle');
        var dropdown = document.getElementById('notif-dropdown');
        if (bell && bellToggle && dropdown) {
            function setBell(open) {
                dropdown.classList.toggle('hidden', !open);
                bellToggle.setAttribute('aria-expanded', String(open));
            }
            bellToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                setBell(dropdown.classList.contains('hidden'));
            });
            document.addEventListener('click', function (e) {
                if (!bell.contains(e.target)) setBell(false);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') setBell(false);
            });
        }
    })();
</script>
