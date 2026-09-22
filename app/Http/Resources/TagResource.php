<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            /*
             * Additive and non-breaking: whenCounted() emits the field ONLY when
             * the caller eager-loaded the count with withCount('posts'). Every
             * existing consumer is untouched — inside PostResource, where no
             * count is loaded, the key simply does not appear (and a per-tag
             * count would be meaningless there anyway).
             *
             * GET /api/v1/tags does load it, so API clients can now rank
             * categories by how much has been published in them.
             */
            'posts_count' => $this->whenCounted('posts'),
        ];
    }
}
