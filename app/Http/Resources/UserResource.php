<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // Additive: lets an API client render admin-only affordances. The
            // server still enforces every permission regardless of this value.
            'role' => $this->role,
            'is_admin' => $this->isAdmin(),
            'created_at' => $this->created_at,
        ];
    }
}
