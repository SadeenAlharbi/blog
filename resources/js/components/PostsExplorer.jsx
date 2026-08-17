import { useEffect, useMemo, useRef, useState } from 'react';
import { DgaSearchBox, DgaChip, DgaCard, DgaPagination } from 'platformscode-new-react';

function excerpt(text, length = 130) {
    const clean = (text || '').replace(/\s+/g, ' ').trim();
    return clean.length > length ? `${clean.slice(0, length)}…` : clean;
}

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

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {posts.map((post) => (
                    <DgaCard
                        key={post.id}
                        cardTitle={post.title}
                        description={excerpt(post.content)}
                        image={post.image_url || ''}
                        effect="with-shadow"
                        showFeaturedIcon={false}
                        showSecondaryAction={false}
                        primaryActionLabel="اقرأ المزيد"
                        onPrimaryAction={() => {
                            window.location.href = `/posts/${post.slug}`;
                        }}
                    />
                ))}
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
