<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'status' => $this->status,
            'author' => new UserResource($this->whenLoaded('user')),
            'post_id' => $this->post_id,
            'created_at' => $this->created_at,
        ];
    }
}
