import { useEffect, useMemo, useRef, useState } from 'react';
// Import from the deep stencil-generated path (NOT the package root). The root
// index.js does `import '@platformscode/core/dist/core/core.css'` — an unlayered
// global reset that breaks Tailwind. This path only registers the web components
// (via each component's defineCustomElement); the global core.css is instead
// loaded, layered, from resources/css/app.css so it can't clobber our styles.
import {
    DgaSearchBox,
    DgaChip,
    DgaPagination,
} from 'platformscode-new-react/dist/components/stencil-generated/components';

function formatDate(value) {
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}/${pad(d.getMonth() + 1)}/${pad(d.getDate())}`;
}

function excerpt(text, length = 130) {
    const clean = (text || '').replace(/\s+/g, ' ').trim();
    return clean.length > length ? `${clean.slice(0, length)}…` : clean;
}

// Posts without a cover image fall back to this data-URI placeholder, which
// matches the Blade card partial's brand-gradient fallback.
const PLACEHOLDER_IMAGE =
    'data:image/svg+xml;utf8,' +
    encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="400" height="225" viewBox="0 0 400 225">
            <defs>
                <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#d1e9d8"/>
                    <stop offset="100%" stop-color="#f3e6cf"/>
                </linearGradient>
            </defs>
            <rect width="400" height="225" fill="url(#g)"/>
            <text x="200" y="130" font-family="Tajawal, sans-serif" font-size="64"
                  fill="#166534" fill-opacity="0.35" text-anchor="middle">م</text>
        </svg>
    `);

export default function PostsExplorer({ apiUrl, tagsApiUrl, initialSearch, initialTag }) {
    const [search, setSearch] = useState(initialSearch || '');
    const [tag, setTag] = useState(initialTag || null);
    const [page, setPage] = useState(1);
    const [posts, setPosts] = useState([]);
    const [tags, setTags] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
    const [loading, setLoading] = useState(true);
    const debounceRef = useRef(null);

    useEffect(() => {
        fetch(tagsApiUrl)
            .then((res) => res.json())
            .then((json) => setTags(json.data || []))
            .catch(() => setTags([]));
    }, [tagsApiUrl]);

    useEffect(() => {
        clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            setLoading(true);
            const params = new URLSearchParams();
            if (search) params.set('search', search);
            if (tag) params.set('tag', tag);
            params.set('page', page);
            params.set('per_page', 9);

            fetch(`${apiUrl}?${params.toString()}`)
                .then((res) => res.json())
                .then((json) => {
                    setPosts(json.data || []);
                    setMeta(json.meta || { current_page: 1, last_page: 1 });
                })
                .catch(() => setPosts([]))
                .finally(() => setLoading(false));

            const url = new URL(window.location.href);
            search ? url.searchParams.set('search', search) : url.searchParams.delete('search');
            tag ? url.searchParams.set('tag', tag) : url.searchParams.delete('tag');
            window.history.replaceState({}, '', url);
        }, 350);

        return () => clearTimeout(debounceRef.current);
    }, [apiUrl, search, tag, page]);

    const handleSearchChange = (event) => {
        setPage(1);
        setSearch(event?.detail?.target?.value ?? '');
    };

    const toggleTag = (slug, isSelected) => {
        setPage(1);
        setTag(isSelected ? slug : null);
    };

    const resultsLabel = useMemo(() => {
        if (loading) return 'جارِ التحميل...';
        if (posts.length === 0) return 'لا توجد نتائج مطابقة.';
        return `${meta.total ?? posts.length} مقال`;
    }, [loading, posts, meta]);

    return (
        <div>
            <div className="mb-5">
                <DgaSearchBox
                    value={search}
                    onOnChange={handleSearchChange}
                    placeholder="ابحث عن مقال بالعنوان أو المحتوى..."
                    speechLang="ar"
                    size="lg"
                    fullwidth
                />
            </div>

            {tags.length > 0 && (
                <div className="flex flex-wrap gap-2 mb-6">
                    {tags.map((t) => (
                        <DgaChip
                            key={t.id}
                            label={t.name}
                            variant={tag === t.slug ? 'success' : 'neutral'}
                            isSelected={tag === t.slug}
                            rounded
                            onChange={(isSelected) => toggleTag(t.slug, isSelected)}
                        />
                    ))}
                </div>
            )}

            <p className="text-xs text-ink-400 mb-4">{resultsLabel}</p>

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 items-stretch">
                {posts.map((post) => {
                    const url = `/posts/${post.slug}`;
                    const open = () => {
                        window.location.href = url;
                    };

                    /*
                        The card is built here rather than with DgaCard because the
                        "اقرأ المزيد" button has to live INSIDE the card, at its
                        bottom edge. DgaCard closes its own box before the button
                        could be added, so the button rendered underneath the card
                        as a loose element in the grid.

                        The structure mirrors resources/views/partials/post-card
                        one for one, so the article list and the home page draw the
                        same card: h-full + flex-col on the article, and mt-auto on
                        the footer, which keeps every card the same height and pins
                        the button to the bottom whatever the excerpt's length.
                    */
                    return (
                        <article
                            key={post.id}
                            role="link"
                            tabIndex={0}
                            onClick={open}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    open();
                                }
                            }}
                            className="group relative flex h-full cursor-pointer flex-col overflow-hidden rounded-2xl border border-ink-100 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-ink-200 hover:shadow-lg hover:shadow-ink-900/5"
                        >
                            <div className="aspect-[16/9] overflow-hidden bg-gradient-to-br from-brand-100 to-sand-100">
                                <img
                                    src={post.image_url || PLACEHOLDER_IMAGE}
                                    alt={post.title}
                                    loading="lazy"
                                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                            </div>

                            <div className="flex min-w-0 flex-1 flex-col p-5">
                                {Array.isArray(post.tags) && post.tags.length > 0 && (
                                    <div className="mb-2.5 flex flex-wrap gap-1.5">
                                        {post.tags.slice(0, 2).map((t) => (
                                            <span
                                                key={t.id || t.slug}
                                                className="inline-flex items-center rounded-full border border-ink-200 bg-white px-2.5 py-1 text-xs font-medium text-ink-600"
                                            >
                                                {t.name}
                                            </span>
                                        ))}
                                    </div>
                                )}

                                <h3 className="mb-2 line-clamp-2 font-bold leading-snug text-ink-900 transition-colors group-hover:text-brand-600">
                                    {post.title}
                                </h3>

                                <p className="mb-4 line-clamp-3 text-sm leading-relaxed text-ink-500">
                                    {excerpt(post.content)}
                                </p>

                                {/* mt-auto pins this row to the bottom of the card. */}
                                <div className="mt-auto flex items-center justify-between gap-3 border-t border-ink-50 pt-3">
                                    <a
                                        href={url}
                                        onClick={(e) => e.stopPropagation()}
                                        className="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700"
                                    >
                                        اقرأ المزيد
                                    </a>

                                    {post.published_at && (
                                        // Same Y/m/d shape the Blade card uses, so the
                                        // two article surfaces read identically.
                                        <span className="shrink-0 text-xs text-ink-300">
                                            {formatDate(post.published_at)}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </article>
                    );
                })}
            </div>

            {meta.last_page > 1 && (
                <div className="mt-8 flex justify-center">
                    <DgaPagination
                        currentPage={meta.current_page || 1}
                        totalPageCount={meta.last_page || 1}
                        onChange={(newPage) => setPage(newPage)}
                    />
                </div>
            )}
        </div>
    );
}
