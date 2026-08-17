<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Support\Str;

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

    public function store(StoreTagRequest $request)
    {
        $tag = Tag::create([
            'name' => $request->validated('name'),
            'slug' => Str::slug($request->validated('name')),
        ]);

        return response()->json([
            'data' => new TagResource($tag),
            'message' => 'Tag created successfully.',
        ], 201);
    }
}
