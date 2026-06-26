<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $date = $this->created_at;

        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'created_at' => $date instanceof \Illuminate\Support\Carbon
                ? $date->format('Y-m-d H:i:s')
                : $date,
        ];
    }
}
