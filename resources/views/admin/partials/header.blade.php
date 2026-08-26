@php
    $adminUnread = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $adminRecent = auth()->check() ? auth()->user()->notifications()->latest()->take(6)->get() : collect();
@endphp
<header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-ink-100">
    <div class="h-1 w-full bg-gradient-to-l from-[#074D31] via-brand-500 to-brand-600"></div>

    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        {{-- Brand --}}
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 shrink-0">
            <x-logo :size="38" />
            <span class="leading-tight hidden sm:block">
                <span class="block font-bold text-ink-900 text-sm">منصة المعرفة السعودية</span>
                <span class="block text-[11px] text-brand-600 font-semibold">لوحة الإدارة</span>
            </span>
        </a>

        <div class="flex items-center gap-2 sm:gap-3">
            <a href="{{ route('home') }}" title="عرض الموقع"
               class="hidden sm:inline-flex items-center gap-1.5 rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-ink-600 hover:border-brand-300 hover:text-brand-700 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                عرض الموقع
            </a>

            {{-- Notification bell (real Laravel database notifications) --}}
            <div class="relative" id="admin-notif">
                <button type="button" id="admin-notif-toggle"
                        class="relative inline-flex items-center justify-center h-10 w-10 rounded-lg text-ink-500 hover:bg-ink-50 hover:text-brand-600 transition-colors"
                        aria-haspopup="true" aria-expanded="false" aria-label="الإشعارات">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    @if ($adminUnread > 0)
                        <span class="absolute -top-0.5 -end-0.5 min-w-[18px] h-[18px] px-1 inline-flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold leading-none ring-2 ring-white">
                            {{ $adminUnread > 9 ? '9+' : $adminUnread }}
                        </span>
                    @endif
                </button>

                <div id="admin-notif-dropdown" class="hidden absolute end-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-2xl border border-ink-100 shadow-xl shadow-ink-900/10 overflow-hidden z-40">
                    <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-ink-100">
                        <span class="text-sm font-bold text-ink-900">الإشعارات</span>
                        @if ($adminUnread > 0)
                            <form method="POST" action="{{ route('admin.notifications.readAll') }}">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-brand-600 hover:text-brand-700">تعليم الكل كمقروء</button>
                            </form>
                        @endif
                    </div>

                    <div class="max-h-80 overflow-y-auto divide-y divide-ink-50">
                        @forelse ($adminRecent as $note)
                            @php $d = $note->data; $unread = is_null($note->read_at); @endphp
                            <form method="POST" action="{{ route('admin.notifications.read', $note->id) }}">
                                @csrf
                                <button type="submit" class="w-full text-start px-4 py-3 transition-colors hover:bg-ink-50 {{ $unread ? 'bg-brand-50/60' : '' }}">
                                    <span class="block text-xs text-ink-700 leading-relaxed">
                                        {{-- Admin-action notifications carry a ready-made sentence;
                                             the older comment notification is composed from its parts. --}}
                                        @if (! empty($d['message']))
                                            {{ $d['message'] }}
                                        @else
                                            <span class="font-semibold text-ink-900">{{ $d['commenter_name'] ?? 'مستخدم' }}</span>
                                            علّق على مقالك
                                            <span class="font-semibold text-brand-700">«{{ \Illuminate\Support\Str::limit($d['post_title'] ?? '', 34) }}»</span>
                                        @endif
                                    </span>
                                    <span class="block text-[11px] text-ink-300 mt-1">{{ $note->created_at->diffForHumans() }}</span>
                                </button>
                            </form>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-ink-400">لا توجد إشعارات بعد.</p>
                        @endforelse
                    </div>

                    <a href="{{ route('admin.notifications.index') }}" class="block text-center px-4 py-3 text-xs font-semibold text-brand-600 hover:bg-ink-50 border-t border-ink-100">عرض كل الإشعارات</a>
                </div>
            </div>

            {{-- Admin identity --}}
            <div class="flex items-center gap-2.5 ps-2 sm:ps-3 border-s border-ink-100">
                <x-avatar :name="auth()->user()->name" :size="34" />
                <span class="hidden md:block leading-tight">
                    <span class="block text-sm font-semibold text-ink-800">{{ auth()->user()->name }}</span>
                    <span class="block text-[11px] text-brand-600">{{ auth()->user()->roleLabel() }}</span>
                </span>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                @csrf
                <button type="submit" title="تسجيل الخروج" aria-label="تسجيل الخروج"
                        class="inline-flex items-center justify-center h-10 w-10 rounded-lg text-ink-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" /></svg>
                </button>
            </form>

            {{-- Sidebar drawer toggle (small screens) --}}
            <button type="button" id="admin-sidebar-toggle" aria-controls="admin-sidebar" aria-expanded="false" aria-label="القائمة"
                    class="lg:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg text-ink-600 hover:bg-ink-50 transition-colors">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
            </button>
        </div>
    </div>
</header>

<script>
    (function () {
        var box = document.getElementById('admin-notif');
        var btn = document.getElementById('admin-notif-toggle');
        var dd = document.getElementById('admin-notif-dropdown');
        if (!box || !btn || !dd) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            dd.classList.toggle('hidden');
            btn.setAttribute('aria-expanded', String(!dd.classList.contains('hidden')));
        });
        document.addEventListener('click', function (e) {
            if (!box.contains(e.target)) dd.classList.add('hidden');
        });
    })();
</script>
