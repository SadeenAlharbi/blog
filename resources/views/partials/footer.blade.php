{{--
    Compact Saudi-green footer.

    Only two content sections, both fed with REAL data by the view composer in
    AppServiceProvider (cached briefly so it costs the site nothing):
      - الأحدث نشرًا  → newest PUBLISHED articles (drafts and scheduled excluded)
      - الأكثر قراءة  → highest recorded view counts

    Titles only: no dates, no view counts, no navigation columns, no social
    links — every entry is just the article name, and clicking it opens it.
--}}
<footer class="text-white" style="background:radial-gradient(circle at 85% 20%,rgba(80,160,125,0.08),transparent 30%),#063f32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-5">
        <div class="grid gap-8 md:grid-cols-4">
            {{-- Brand --}}
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5 mb-2.5">
                    <x-logo :size="40" />
                    <span class="font-bold leading-tight">منصة المعرفة السعودية</span>
                </div>
                <p class="text-sm text-white/65 leading-relaxed max-w-sm">
                    منصة معرفية سعودية توثّق قصة المملكة وتحولاتها، وتواكب ما تصنعه نحو المستقبل.
                </p>
            </div>

            {{-- Latest published --}}
            <div>
                <h3 class="text-sm font-semibold pb-1.5 mb-2 border-b border-white/20">الأحدث نشرًا</h3>
                @forelse ($footerLatest ?? [] as $item)
                    <a href="{{ route('posts.show', $item['slug']) }}"
                       class="block py-1.5 text-sm text-white/70 hover:text-white transition-colors line-clamp-1">
                        {{ $item['title'] }}
                    </a>
                @empty
                    <p class="text-sm text-white/45 py-1.5">لا توجد مقالات منشورة بعد.</p>
                @endforelse
            </div>

            {{-- Most read --}}
            <div>
                <h3 class="text-sm font-semibold pb-1.5 mb-2 border-b border-white/20">الأكثر قراءة</h3>
                @forelse ($footerMostRead ?? [] as $item)
                    <a href="{{ route('posts.show', $item['slug']) }}"
                       class="block py-1.5 text-sm text-white/70 hover:text-white transition-colors line-clamp-1">
                        {{ $item['title'] }}
                    </a>
                @empty
                    <p class="text-sm text-white/45 py-1.5">لا توجد مشاهدات بعد.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="border-t border-white/15">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p class="text-xs text-white/60 text-center sm:text-start">
                &copy; {{ date('Y') }} منصة المعرفة السعودية. جميع الحقوق محفوظة.
            </p>
            <p class="text-xs text-white/50">معرفة موثوقة تواكب رؤية المملكة 2030</p>
        </div>
    </div>
</footer>
