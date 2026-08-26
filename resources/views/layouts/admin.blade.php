<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'لوحة الإدارة') — منصة المعرفة السعودية</title>

        @fonts
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    {{-- Warm off-white ground: the admin reads as the same product as the site,
         with the Saudi green kept as an accent rather than a page-wide wash. --}}
    <body class="min-h-screen bg-ink-25 text-ink-800 antialiased">
        <a href="#admin-main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:start-3 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:text-white">
            تخطَّ إلى المحتوى
        </a>

        @include('admin.partials.header')

        <div class="max-w-[1600px] mx-auto flex">
            @include('admin.partials.sidebar')

            <main id="admin-main" class="flex-1 min-w-0 px-4 sm:px-6 lg:px-8 py-6">
                @if (session('success'))
                    <div class="mb-5"><x-alert type="success">{{ session('success') }}</x-alert></div>
                @endif

                @if (session('error'))
                    <div class="mb-5"><x-alert type="error">{{ session('error') }}</x-alert></div>
                @endif

                @if ($errors->any())
                    <div class="mb-5">
                        <x-alert type="error">
                            <p class="font-medium mb-1">يرجى تصحيح الأخطاء التالية:</p>
                            <ul class="list-disc ps-5 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

        <footer class="border-t border-ink-100 bg-white">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center">
                <p class="text-xs text-ink-400">&copy; {{ date('Y') }} منصة المعرفة السعودية — لوحة الإدارة</p>
            </div>
        </footer>

        @include('partials.toasts')

        {{-- Shared confirmation modal (used by every destructive form). --}}
        <div id="confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-ink-900/40" data-close-modal></div>
            <div role="dialog" aria-modal="true" class="relative w-full max-w-sm rounded-2xl bg-white border border-ink-100 shadow-xl p-6">
                <h3 id="confirm-modal-title" class="text-base font-bold text-ink-900 mb-2">تأكيد الإجراء</h3>
                <p id="confirm-modal-text" class="text-sm text-ink-500 leading-relaxed mb-5"></p>
                <div class="flex items-center gap-2 justify-start">
                    <button type="button" id="confirm-modal-ok" class="inline-flex items-center rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-700 transition-colors">تأكيد</button>
                    <button type="button" data-close-modal class="inline-flex items-center rounded-xl border border-ink-200 bg-white px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-50 transition-colors">إلغاء</button>
                </div>
            </div>
        </div>
        {{-- These scripts run AFTER the markup they bind to (the sidebar above
             and the confirmation modal just above), so every getElementById()
             here resolves. Moving this block earlier silently disables the
             confirmation dialog. --}}
        <script>
            // Sidebar drawer on small screens.
            (function () {
                var toggle = document.getElementById('admin-sidebar-toggle');
                var sidebar = document.getElementById('admin-sidebar');
                var backdrop = document.getElementById('admin-sidebar-backdrop');
                if (!toggle || !sidebar) return;

                function setOpen(open) {
                    sidebar.classList.toggle('-translate-x-full', false);
                    sidebar.classList.toggle('hidden', !open);
                    sidebar.classList.toggle('fixed', open);
                    sidebar.classList.toggle('inset-y-0', open);
                    sidebar.classList.toggle('end-0', open);
                    sidebar.classList.toggle('z-40', open);
                    if (backdrop) backdrop.classList.toggle('hidden', !open);
                    toggle.setAttribute('aria-expanded', String(open));
                }

                toggle.addEventListener('click', function () {
                    setOpen(sidebar.classList.contains('hidden'));
                });
                if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') setOpen(false);
                });
            })();

            // Confirmation modal for destructive actions.
            (function () {
                var modal = document.getElementById('confirm-modal');
                if (!modal) return;
                var titleEl = document.getElementById('confirm-modal-title');
                var textEl = document.getElementById('confirm-modal-text');
                var okBtn = document.getElementById('confirm-modal-ok');
                var pendingForm = null;

                function close() { modal.classList.add('hidden'); pendingForm = null; }

                document.addEventListener('submit', function (e) {
                    var form = e.target;
                    if (!form.matches('[data-confirm]')) return;
                    if (form.dataset.confirmed === '1') return;
                    e.preventDefault();
                    pendingForm = form;
                    titleEl.textContent = form.dataset.confirmTitle || 'تأكيد الإجراء';
                    textEl.textContent = form.dataset.confirm;
                    okBtn.textContent = form.dataset.confirmOk || 'تأكيد';
                    modal.classList.remove('hidden');
                });

                okBtn.addEventListener('click', function () {
                    if (!pendingForm) return;
                    pendingForm.dataset.confirmed = '1';
                    pendingForm.submit();
                });

                modal.querySelectorAll('[data-close-modal]').forEach(function (el) {
                    el.addEventListener('click', close);
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') close();
                });
            })();
        </script>
    </body>
</html>
