@props(['label' => 'المتابعة باستخدام Google'])

{{--
    One button, used by both the sign-in and the sign-up page — the OAuth flow
    behind it is the same either way (GoogleAuthController).

    It renders only when Google is actually configured, so an installation
    without credentials never shows a control that cannot work. Add
    GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to .env and it appears.

    Styling deliberately reuses the tokens already on these pages — the same
    rounded-xl, px-5 py-2.5, text-sm font-semibold and transition-colors as the
    primary button — so it reads as part of the existing design, not an import.
--}}
@if (\App\Http\Controllers\Auth\GoogleAuthController::isConfigured())
    {{-- mt-6 mb-5 rather than my-5: both are already in the compiled
         stylesheet, so this needs no Tailwind rebuild to render correctly. --}}
    <div class="flex items-center gap-3 mt-6 mb-5">
        <span class="h-px flex-1 bg-ink-100"></span>
        <span class="text-xs text-ink-400">أو</span>
        <span class="h-px flex-1 bg-ink-100"></span>
    </div>

    <a href="{{ route('auth.google.redirect') }}"
       class="w-full inline-flex justify-center items-center gap-3 rounded-xl border border-ink-200 bg-white px-5 py-2.5 text-sm font-semibold text-ink-700 hover:border-ink-300 transition-colors">
        {{-- Google's official mark. Inline so it needs no asset request. --}}
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        {{ $label }}
    </a>
@endif
