{{--
    Toast notifications.

    Two sources feed the same stack:
      1. Server flashes — e.g. `removed_notice` when a reader follows a link to
         an article a moderator removed.
      2. window.pushToast(message, type) — used by the article page's liveness
         poll when a post or comment disappears while it is open.

    Plain JS + Tailwind, matching the rest of the project (no new dependency).
    Each toast fades out on its own after 7 seconds.
--}}
{{--
    Server-flashed notices ride on data-* attributes rather than being printed
    into the script, so the Arabic text stays readable in the HTML source
    (@json escapes it to \uXXXX) and screen readers reach it too.
--}}
<div id="toast-stack"
     class="fixed z-[60] bottom-5 inset-x-4 sm:inset-x-auto sm:end-5 sm:w-96 flex flex-col gap-2 pointer-events-none"
     @if (session('removed_notice')) data-flash-warning="{{ session('removed_notice') }}" @endif
     @if (session('toast')) data-flash-info="{{ session('toast') }}" @endif
     role="status" aria-live="polite"></div>

{{-- Readable without JavaScript, and what assertSee() finds in tests. --}}
@if (session('removed_notice') || session('toast'))
    <noscript>
        <p class="mx-auto max-w-3xl px-4 py-3 text-sm text-ink-700">
            {{ session('removed_notice') ?: session('toast') }}
        </p>
    </noscript>
@endif

<script>
    (function () {
        var TTL = 7000; // the notice stays for ~7 seconds, then leaves
        var stack = document.getElementById('toast-stack');
        if (!stack) return;

        var palette = {
            info:    { bg: 'bg-white',      ring: 'ring-ink-200',   bar: 'bg-brand-500', text: 'text-ink-800' },
            success: { bg: 'bg-white',      ring: 'ring-brand-200', bar: 'bg-brand-500', text: 'text-ink-800' },
            warning: { bg: 'bg-white',      ring: 'ring-amber-200', bar: 'bg-amber-500', text: 'text-ink-800' },
            error:   { bg: 'bg-white',      ring: 'ring-red-200',   bar: 'bg-red-500',   text: 'text-ink-800' }
        };

        function pushToast(message, type) {
            if (!message) return;
            var tone = palette[type] || palette.info;

            var el = document.createElement('div');
            el.className = 'pointer-events-auto flex items-start gap-3 rounded-2xl ' + tone.bg +
                ' ring-1 ' + tone.ring + ' shadow-lg shadow-ink-900/10 overflow-hidden ' +
                'opacity-0 translate-y-2 transition-all duration-300';

            var bar = document.createElement('span');
            bar.className = 'w-1 self-stretch shrink-0 ' + tone.bar;

            var body = document.createElement('div');
            body.className = 'flex-1 min-w-0 py-3 pe-2 ps-1';
            var p = document.createElement('p');
            p.className = 'text-sm leading-relaxed ' + tone.text;
            p.textContent = message;
            body.appendChild(p);

            var close = document.createElement('button');
            close.type = 'button';
            close.setAttribute('aria-label', 'إغلاق التنبيه');
            close.className = 'shrink-0 p-3 text-ink-300 hover:text-ink-600 transition-colors';
            close.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';

            el.appendChild(bar);
            el.appendChild(body);
            el.appendChild(close);
            stack.appendChild(el);

            requestAnimationFrame(function () {
                el.classList.remove('opacity-0', 'translate-y-2');
            });

            var timer = setTimeout(dismiss, TTL);
            close.addEventListener('click', function () { clearTimeout(timer); dismiss(); });

            function dismiss() {
                el.classList.add('opacity-0', 'translate-y-2');
                setTimeout(function () { el.remove(); }, 300);
            }
        }

        window.pushToast = pushToast;

        // Server-flashed notices, read back off the container's data-* attributes.
        pushToast(stack.dataset.flashWarning, 'warning');
        pushToast(stack.dataset.flashInfo, 'info');
    })();
</script>
