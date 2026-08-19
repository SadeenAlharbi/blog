{{-- Shared sub-navigation for the personal page. --}}
<div role="tablist" class="flex items-center gap-1 border-b border-ink-100 mb-8 overflow-x-auto">
    <a href="{{ route('dashboard') }}"
       class="shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors {{ request()->routeIs('dashboard') ? 'border-brand-600 text-brand-700' : 'border-transparent text-ink-500 hover:text-ink-800' }}">
        مقالاتي
    </a>
    <a href="{{ route('dashboard.comments') }}"
       class="shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors {{ request()->routeIs('dashboard.comments') ? 'border-brand-600 text-brand-700' : 'border-transparent text-ink-500 hover:text-ink-800' }}">
        تعليقاتي
    </a>
</div>
