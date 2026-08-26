@props([
    'note',
    'action',        // route the "mark as read" form posts to
    'size' => 'md',  // 'sm' inside the header bell, 'md' on a full page
])

@php
    /*
        One row of the notification list, used by all three surfaces so an
        article notice can never again be drawn with the comment icon and the
        comment sentence. Everything type-specific comes from
        NotificationPresenter — this file only owns the markup.
    */
    $view = \App\Support\NotificationPresenter::make($note->data ?? []);
    $unread = is_null($note->read_at);
    $small = $size === 'sm';

    // Icon paths taken from the set the admin dashboard already uses.
    $icons = [
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>',
        'pencil' => '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/>',
        'trash' => '<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>',
        'bell' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>',
    ];

    $tones = [
        'brand' => 'bg-brand-100 text-brand-600',
        'amber' => 'bg-amber-100 text-amber-700',
        'red' => 'bg-red-100 text-red-600',
    ];

    $iconPath = $icons[$view['icon']] ?? $icons['bell'];
    $toneClass = $tones[$view['tone']] ?? $tones['brand'];
@endphp

<form method="POST" action="{{ $action }}" @class(['mb-3' => ! $small])>
    @csrf
    <button type="submit"
            @class([
                'w-full text-start flex transition-colors' => true,
                'px-4 py-3 gap-3 hover:bg-ink-50' => $small,
                'gap-4 rounded-2xl border p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:shadow-ink-900/5' => ! $small,
                'bg-brand-50/60' => $small && $unread,
                'bg-brand-50/50 border-brand-100' => ! $small && $unread,
                'bg-white border-ink-100' => ! $small && ! $unread,
            ])>
        <span @class(['shrink-0', 'mt-1' => $small])>
            <span class="flex items-center justify-center rounded-full {{ $toneClass }} {{ $small ? 'h-8 w-8' : 'h-10 w-10' }}">
                <svg class="{{ $small ? 'h-4 w-4' : 'h-5 w-5' }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    {!! $iconPath !!}
                </svg>
            </span>
        </span>

        <span class="min-w-0 flex-1">
            <span class="block leading-relaxed text-ink-700 {{ $small ? 'text-xs' : 'text-sm' }}">
                {{ $view['message'] }}
            </span>

            @if ($view['subject'])
                <span class="block font-semibold text-brand-700 mt-0.5 line-clamp-1 {{ $small ? 'text-xs' : 'text-sm' }}">
                    «{{ $view['subject'] }}»
                </span>
            @endif

            @if (! empty($note->data['excerpt']))
                <span class="block text-ink-500 mt-1 {{ $small ? 'text-xs line-clamp-1' : 'text-sm line-clamp-2' }}">
                    {{ $note->data['excerpt'] }}
                </span>
            @endif

            <span class="block text-ink-300 {{ $small ? 'text-[11px] mt-1' : 'text-xs mt-1.5' }}">
                {{ $note->created_at->diffForHumans() }}
            </span>
        </span>

        @if ($unread)
            <span class="shrink-0 rounded-full bg-brand-500 {{ $small ? 'mt-1 h-2 w-2' : 'mt-1.5 h-2.5 w-2.5' }}" aria-label="غير مقروء"></span>
        @endif
    </button>
</form>
