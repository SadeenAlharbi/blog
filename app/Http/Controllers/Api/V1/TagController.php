<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('posts')->orderBy('name')->get();

        return response()->json([
            'data' => TagResource::collection($tags),
            'message' => 'OK',
        ]);
    }

    /**
     * Materialise a canonical category.
     *
     * StoreTagRequest already restricts the input to the central list, so this
     * can only ever create one of the platform's own categories — never a
     * free-text tag. The slug is resolved FROM that list rather than generated
     * with Str::slug(), which would produce an empty slug for an Arabic name.
     * Idempotent: asking twice returns the existing category.
     */
    public function store(StoreTagRequest $request)
    {
        $value = $request->validated('name');
        $categories = Tag::categories();

        // The client may send either the slug or the exact Arabic name.
        $slug = array_key_exists($value, $categories)
            ? $value
            : array_search($value, $categories, true);

        $name = $categories[$slug];

        $tag = Tag::firstOrCreate(['slug' => $slug], ['name' => $name]);

        return response()->json([
            'data' => new TagResource($tag),
            'message' => $tag->wasRecentlyCreated
                ? 'Tag created successfully.'
                : 'Tag already exists.',
        ], $tag->wasRecentlyCreated ? 201 : 200);
    }
}
