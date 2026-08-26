@php
    // Sidebar groups. Counts are real, from the database.
    $navGroups = [
        [
            'label' => null,
            'items' => [
                ['label' => 'الرئيسية', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'home'],
            ],
        ],
        [
            'label' => 'المحتوى',
            'items' => [
                // Article creation lives on the writer's own page, not here.
                ['label' => 'المقالات', 'route' => 'admin.posts.index', 'active' => 'admin.posts.*', 'icon' => 'document'],
                ['label' => 'التصنيفات', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'icon' => 'tag'],
            ],
        ],
        [
            'label' => 'التفاعل',
            'items' => [
                ['label' => 'التعليقات', 'route' => 'admin.comments.index', 'active' => 'admin.comments.*', 'icon' => 'chat'],
                ['label' => 'المستخدمون', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'users'],
            ],
        ],
        [
            'label' => 'التحليلات',
            'items' => [
                ['label' => 'إحصائيات المحتوى', 'route' => 'admin.analytics.index', 'active' => 'admin.analytics.*', 'icon' => 'chart'],
            ],
        ],
        [
            'label' => 'النظام',
            'items' => [
                ['label' => 'الإشعارات', 'route' => 'admin.notifications.index', 'active' => 'admin.notifications.*', 'icon' => 'bell'],
            ],
        ],
    ];

    $navIcons = [
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>',
        'plus' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>',
        'tag' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>',
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>',
        'bell' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>',
        'cog' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.03 7.03 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
    ];
@endphp

<div id="admin-sidebar-backdrop" class="hidden fixed inset-0 bg-ink-900/40 z-30 lg:hidden"></div>

<aside id="admin-sidebar"
       class="hidden lg:block w-64 shrink-0 bg-white lg:bg-transparent border-s border-ink-100 lg:border-s-0 lg:border-e lg:border-ink-100 overflow-y-auto">
    <div class="p-4 lg:py-6 lg:pe-6 lg:ps-0 space-y-6">
        @foreach ($navGroups as $group)
            <div>
                @if ($group['label'])
                    <p class="px-3 mb-1.5 text-[11px] font-bold text-ink-400 tracking-wide">{{ $group['label'] }}</p>
                @endif
                <ul class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @php $isActive = request()->routeIs($item['active']); @endphp
                        <li>
                            <a href="{{ route($item['route']) }}"
                               class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors {{ $isActive ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-100' : 'text-ink-600 hover:bg-ink-50 hover:text-ink-900' }}"
                               @if ($isActive) aria-current="page" @endif>
                                <svg class="h-[18px] w-[18px] shrink-0 {{ $isActive ? 'text-brand-600' : 'text-ink-400' }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">{!! $navIcons[$item['icon']] !!}</svg>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</aside>
