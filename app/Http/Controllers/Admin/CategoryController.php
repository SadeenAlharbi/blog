<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;

/**
 * Category management.
 *
 * Categories ARE the project's tags — one system, not two. `Tag::categories()`
 * remains the list the platform ships with, while the `tags` rows are the live,
 * moderator-managed set that the post form reads through `Tag::options()`.
 *
 * Removing a category soft-deletes it, so it (and its article links) can be
 * brought back from the restore dialog.
 */
class CategoryController extends Controller
{
    public function index()
    {
        $tags = Tag::query()
            ->withCount([
                'posts',
                'posts as published_posts_count' => fn ($q) => $q->where('status', Post::STATUS_PUBLISHED),
            ])
            ->orderBy('name')
            ->get();

        // Everything the moderator can bring back: categories that were
        // removed, plus shipped defaults that have no row at all.
        $removed = Tag::onlyTrashed()->withCount('posts')->orderBy('name')->get();

        $known = $tags->pluck('slug')->merge($removed->pluck('slug'))->all();
        $missingDefaults = array_diff_key(Tag::categories(), array_flip($known));

        return view('admin.categories.index', [
            'tags' => $tags,
            'removed' => $removed,
            'missingDefaults' => $missingDefaults,
        ]);
    }

    /**
     * Add a category from a name alone.
     *
     * The moderator never supplies an id or a slug — both are generated here.
     * A name that matches a REMOVED category restores that row instead of
     * creating a second one, which keeps the old article links.
     */
    public function store(StoreCategoryRequest $request)
    {
        $name = $request->validated('name');

        $existing = Tag::findByName($name, withTrashed: true);

        if ($existing && $existing->trashed()) {
            $existing->restore();

            return back()->with('success', "تمت استعادة تصنيف «{$existing->name}» من التصنيفات المحذوفة.");
        }

        $tag = Tag::create([
            'name' => $name,
            'slug' => Tag::makeSlug($name),
        ]);

        return back()->with('success', "تمت إضافة تصنيف «{$tag->name}».");
    }

    /** Rename a category. The slug stays fixed so existing links keep working. */
    public function update(UpdateCategoryRequest $request, Tag $category)
    {
        $category->update(['name' => $request->validated('name')]);

        return back()->with('success', 'تم تحديث اسم التصنيف.');
    }

    /**
     * Remove a category.
     *
     * Refused while any article still uses it — removing it would strip those
     * articles of their category silently. The moderator is told how many
     * articles are in the way.
     */
    public function destroy(Tag $category)
    {
        $inUse = $category->posts()->count();

        if ($inUse > 0) {
            return back()->with(
                'error',
                "لا يمكن حذف «{$category->name}» لأنه مستخدم في {$inUse} مقال. أزِل التصنيف من تلك المقالات أولاً."
            );
        }

        $name = $category->name;
        $category->delete(); // soft delete — restorable

        return back()->with('success', "تم حذف تصنيف «{$name}». يمكنك استرداده لاحقاً.");
    }

    /**
     * Restore ONLY the categories the moderator ticked.
     *
     * Two kinds of entry appear in the dialog and arrive in the same request:
     *   ids[]   — soft-deleted rows, brought back with their article links
     *   slugs[] — shipped defaults that never had a row, created now
     *
     * Nothing is restored when the selection is empty.
     */
    public function restore(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
            'slugs' => ['nullable', 'array'],
            'slugs.*' => ['string'],
        ]);

        $ids = $validated['ids'] ?? [];
        $slugs = $validated['slugs'] ?? [];

        if ($ids === [] && $slugs === []) {
            return back()->with('error', 'يرجى اختيار تصنيف واحد على الأقل.');
        }

        $restored = 0;

        // Removed categories — restored in place, article links intact.
        foreach (Tag::onlyTrashed()->whereIn('id', $ids)->get() as $tag) {
            $tag->restore();
            $restored++;
        }

        // Shipped defaults with no row yet — only the ticked ones are created.
        $defaults = Tag::categories();

        foreach ($slugs as $slug) {
            if (! isset($defaults[$slug])) {
                continue; // not a known default: ignored rather than trusted
            }

            $existing = Tag::withTrashed()->where('slug', $slug)->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                    $restored++;
                }

                continue;
            }

            Tag::create(['slug' => $slug, 'name' => $defaults[$slug]]);
            $restored++;
        }

        if ($restored === 0) {
            return back()->with('error', 'لم يتم استرداد أي تصنيف.');
        }

        return back()->with('success', "تم استرداد {$restored} تصنيفاً.");
    }
}
