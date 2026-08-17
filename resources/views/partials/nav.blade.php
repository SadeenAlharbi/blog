<header class="border-b border-ink-100 bg-white/80 backdrop-blur sticky top-0 z-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white font-bold">م</span>
            <span class="font-bold text-ink-900 leading-tight">
                منصة المعرفة السعودية
            </span>
        </a>

        <nav class="hidden sm:flex items-center gap-6 text-sm font-medium text-ink-600">
            <a href="{{ url('/') }}" class="hover:text-brand-600 transition-colors {{ request()->is('/') ? 'text-brand-600' : '' }}">الرئيسية</a>
            <a href="{{ route('posts.index') }}" class="hover:text-brand-600 transition-colors {{ request()->routeIs('posts.*') ? 'text-brand-600' : '' }}">المقالات</a>
            @auth
                <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors {{ request()->routeIs('dashboard') ? 'text-brand-600' : '' }}">لوحة التحكم</a>
            @endauth
        </nav>

        <div class="flex items-center gap-3">
            @auth
                <span class="hidden md:inline text-sm text-ink-500">مرحباً، {{ auth()->user()->name }}</span>
                <a href="{{ route('posts.create') }}" class="hidden sm:inline-flex items-center rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-semibold hover:bg-brand-700 transition-colors">
                    مقال جديد
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-ink-600 hover:text-red-600 transition-colors">
                        تسجيل الخروج
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-ink-600 hover:text-brand-600 transition-colors">
                    تسجيل الدخول
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-semibold hover:bg-brand-700 transition-colors">
                    إنشاء حساب
                </a>
            @endauth
        </div>
    </div>
</header>
