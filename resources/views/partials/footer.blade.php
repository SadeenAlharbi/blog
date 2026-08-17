<footer class="border-t border-ink-100 bg-white mt-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 grid gap-8 sm:grid-cols-3">
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white font-bold text-sm">م</span>
                <span class="font-bold text-ink-900">منصة المعرفة السعودية</span>
            </div>
            <p class="text-sm text-ink-500 leading-relaxed">
                منصة معرفية تغطي تاريخ المملكة، رؤية 2030، الاقتصاد، التقنية، الذكاء الاصطناعي، الثقافة والسياحة والمجتمع.
            </p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink-800 mb-3">استكشف</h3>
            <ul class="space-y-2 text-sm text-ink-500">
                <li><a href="{{ route('posts.index') }}" class="hover:text-brand-600">جميع المقالات</a></li>
                <li><a href="{{ route('posts.index') }}?tag=vision-2030" class="hover:text-brand-600">رؤية 2030</a></li>
                <li><a href="{{ route('posts.index') }}?tag=technology" class="hover:text-brand-600">التقنية</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink-800 mb-3">حول المشروع</h3>
            <p class="text-sm text-ink-500 leading-relaxed">
                مبني بإطار Laravel وReact وTailwind CSS، مع REST API موثّق بالكامل — مشروع تعليمي/عرض تقني.
            </p>
        </div>
    </div>
    <div class="border-t border-ink-100 py-4 text-center text-xs text-ink-400">
        &copy; {{ date('Y') }} منصة المعرفة السعودية. جميع الحقوق محفوظة.
    </div>
</footer>
