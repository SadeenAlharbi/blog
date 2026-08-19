{{--
    Saudi-green platform footer, adapted from the DGA design-system footer template.
    Kept as a real <footer> (block by default, so Platforms Code's core.css reset is harmless here).
    Pure Blade + Tailwind — no JS dependency, RTL-correct, no fixed widths (overflow-safe).
--}}
<footer class="bg-[#074D31] text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-8">
        <div class="grid gap-10 md:grid-cols-12">
            {{-- Brand + about --}}
            <div class="md:col-span-4">
                <div class="flex items-center gap-2.5 mb-4">
                    <x-logo :size="40" />
                    <span class="font-bold leading-tight">منصة المعرفة السعودية</span>
                </div>
                <h3 class="text-sm font-semibold pb-2 mb-3 border-b border-white/20">عن المنصة</h3>
                <p class="text-sm text-white/70 leading-relaxed max-w-sm">
                    منصة معرفية سعودية توثّق قصة المملكة وتحولاتها، وتقدّم محتوى موثوقًا يعرّفك بتاريخها، رؤيتها،
                    اقتصادها، ثقافتها، وتقنيتها، ويواكب ما تصنعه السعودية نحو المستقبل.
                </p>

                <div class="flex items-center gap-2.5 mt-6">
                    {{-- X --}}
                    <a href="#" aria-label="X" class="inline-flex h-9 w-9 items-center justify-center rounded-lg ring-1 ring-white/25 hover:bg-white/10 transition-colors">
                        <svg width="16" height="16" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0.943526 1.21547C1.05038 1.00649 1.2653 0.875 1.5 0.875H5.66667C5.86736 0.875 6.05584 0.971373 6.17334 1.13407L10.2867 6.82945L16.0581 1.05806C16.3021 0.813981 16.6979 0.813981 16.9419 1.05806C17.186 1.30214 17.186 1.69786 16.9419 1.94194L11.028 7.85589L17.0067 16.1341C17.1441 16.3243 17.1633 16.5756 17.0565 16.7845C16.9496 16.9935 16.7347 17.125 16.5 17.125H12.3333C12.1326 17.125 11.9442 17.0286 11.8267 16.8659L7.71333 11.1706L1.94194 16.9419C1.69787 17.186 1.30214 17.186 1.05806 16.9419C0.813983 16.6979 0.813983 16.3021 1.05806 16.0581L6.97201 10.1441L0.993328 1.86593C0.85591 1.67566 0.836676 1.42444 0.943526 1.21547ZM2.72235 2.125L12.6529 15.875H15.2777L5.3471 2.125H2.72235Z" fill="currentColor"/>
                        </svg>
                    </a>
                    {{-- LinkedIn --}}
                    <a href="#" aria-label="LinkedIn" class="inline-flex h-9 w-9 items-center justify-center rounded-lg ring-1 ring-white/25 hover:bg-white/10 transition-colors">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1 4.98 2.12 4.98 3.5zM.25 8h4.5V23H.25V8zm7.5 0h4.31v2.05h.06c.6-1.14 2.07-2.34 4.26-2.34 4.56 0 5.4 3 5.4 6.9V23h-4.5v-6.49c0-1.55-.03-3.54-2.16-3.54-2.16 0-2.49 1.69-2.49 3.43V23h-4.5V8z"/>
                        </svg>
                    </a>
                    {{-- Instagram --}}
                    <a href="#" aria-label="Instagram" class="inline-flex h-9 w-9 items-center justify-center rounded-lg ring-1 ring-white/25 hover:bg-white/10 transition-colors">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 13.125C8.27413 13.125 6.87502 11.7259 6.87502 10C6.87502 8.27412 8.27413 6.87501 10 6.87501C11.7259 6.87501 13.125 8.27412 13.125 10C13.125 11.7259 11.7259 13.125 10 13.125ZM8.12502 10C8.12502 11.0355 8.96449 11.875 10 11.875C11.0356 11.875 11.875 11.0355 11.875 10C11.875 8.96447 11.0356 8.12501 10 8.12501C8.96449 8.12501 8.12502 8.96447 8.12502 10Z" fill="currentColor"/>
                            <path d="M13.5898 5.25001C14.0501 5.25001 14.4232 4.87691 14.4232 4.41668C14.4232 3.95644 14.0501 3.58334 13.5898 3.58334H13.5823C13.1221 3.58334 12.749 3.95644 12.749 4.41668C12.749 4.87691 13.1221 5.25001 13.5823 5.25001H13.5898Z" fill="currentColor"/>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M9.04763 0.458344C10.8732 0.458333 12.3071 0.458325 13.4266 0.608838C14.5738 0.763078 15.4841 1.08561 16.1992 1.80077C16.9144 2.51594 17.2369 3.42615 17.3912 4.57336C17.5417 5.69287 17.5417 7.12676 17.5416 8.95234V9.04765C17.5417 10.8732 17.5417 12.3071 17.3912 13.4267C17.2369 14.5739 16.9144 15.4841 16.1992 16.1992C15.4841 16.9144 14.5738 17.2369 13.4266 17.3912C12.3071 17.5417 10.8732 17.5417 9.04765 17.5417H8.95234C7.12676 17.5417 5.69284 17.5417 4.57333 17.3912C3.42612 17.2369 2.51591 16.9144 1.80074 16.1992C1.08558 15.4841 0.763047 14.5739 0.608808 13.4267C0.458293 12.3071 0.458302 10.8732 0.458313 9.04766V8.95236C0.458302 7.12678 0.458293 5.69288 0.608808 4.57336C0.763047 3.42615 1.08558 2.51594 1.80074 1.80077C2.51591 1.08561 3.42612 0.763078 4.57333 0.608838C5.69284 0.458325 7.12673 0.458333 8.95231 0.458344H9.04763ZM4.73989 1.84769C3.73129 1.9833 3.12883 2.24045 2.68462 2.68465C2.24042 3.12886 1.98326 3.73132 1.84766 4.73992C1.70964 5.76651 1.70831 7.11636 1.70831 9.00001C1.70831 10.8837 1.70964 12.2335 1.84766 13.2601C1.98326 14.2687 2.24042 14.8712 2.68462 15.3154C3.12883 15.7596 3.73129 16.0167 4.73989 16.1523C5.76648 16.2903 7.11633 16.2917 8.99998 16.2917C10.8836 16.2917 12.2335 16.2903 13.2601 16.1523C14.2687 16.0167 14.8711 15.7596 15.3153 15.3154C15.7595 14.8712 16.0167 14.2687 16.1523 13.2601C16.2903 12.2335 16.2916 10.8837 16.2916 9.00001C16.2916 7.11636 16.2903 5.76651 16.1523 4.73992C16.0167 3.73132 15.7595 3.12886 15.3153 2.68465C14.8711 2.24045 14.2687 1.9833 13.2601 1.84769C12.2335 1.70967 10.8836 1.70834 8.99998 1.70834C7.11633 1.70834 5.76648 1.70967 4.73989 1.84769Z" fill="currentColor"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Explore --}}
            <div class="md:col-span-3">
                <h3 class="text-sm font-semibold pb-2 mb-3 border-b border-white/20">استكشف</h3>
                <ul class="space-y-2.5 text-sm text-white/75">
                    <li><a href="{{ route('posts.index') }}" class="hover:text-white transition-colors">جميع المقالات</a></li>
                    <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">الرئيسية</a></li>
                    <li><a href="{{ route('posts.index', ['tag' => 'vision-2030']) }}" class="hover:text-white transition-colors">رؤية السعودية 2030</a></li>
                    <li><a href="{{ route('posts.index', ['tag' => 'hajj-umrah']) }}" class="hover:text-white transition-colors">الحج والعمرة</a></li>
                </ul>
            </div>

            {{-- Topics --}}
            <div class="md:col-span-3">
                <h3 class="text-sm font-semibold pb-2 mb-3 border-b border-white/20">التصنيفات</h3>
                <ul class="space-y-2.5 text-sm text-white/75">
                    <li><a href="{{ route('posts.index', ['tag' => 'history']) }}" class="hover:text-white transition-colors">التاريخ</a></li>
                    <li><a href="{{ route('posts.index', ['tag' => 'vision-2030']) }}" class="hover:text-white transition-colors">رؤية 2030</a></li>
                    <li><a href="{{ route('posts.index', ['tag' => 'economy']) }}" class="hover:text-white transition-colors">الاقتصاد</a></li>
                    <li><a href="{{ route('posts.index', ['search' => 'الذكاء الاصطناعي']) }}" class="hover:text-white transition-colors">التقنية والذكاء الاصطناعي</a></li>
                    <li><a href="{{ route('posts.index', ['tag' => 'culture']) }}" class="hover:text-white transition-colors">الثقافة والتراث</a></li>
                    <li><a href="{{ route('posts.index', ['search' => 'السياحة']) }}" class="hover:text-white transition-colors">السياحة والمجتمع</a></li>
                </ul>
            </div>

            {{-- About / join --}}
            <div class="md:col-span-2">
                <h3 class="text-sm font-semibold pb-2 mb-3 border-b border-white/20">المنصة</h3>
                <ul class="space-y-2.5 text-sm text-white/75">
                    @guest
                        <li><a href="{{ route('register') }}" class="hover:text-white transition-colors">إنشاء حساب</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white transition-colors">تسجيل الدخول</a></li>
                    @else
                        <li><a href="{{ route('dashboard') }}" class="hover:text-white transition-colors">الصفحة الشخصية</a></li>
                        <li><a href="{{ route('posts.create') }}" class="hover:text-white transition-colors">نشر مقال</a></li>
                    @endguest
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-white/15">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-white/60 text-center sm:text-start">
                &copy; {{ date('Y') }} منصة المعرفة السعودية. جميع الحقوق محفوظة.
            </p>
            <p class="text-xs text-white/50">
                معرفة موثوقة تواكب رؤية المملكة 2030
            </p>
        </div>
    </div>
</footer>
