<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => class_basename($this->type),
            'data' => $this->data,
            'read_at' => $this->read_at,
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at,
        ];
    }
}
