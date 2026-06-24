<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlueprintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tone' => $this->tone,
            'max_hashtags' => $this->max_hashtags,
            'max_characters' => $this->max_characters,
            'regles_supplementaires' => $this->regles_supplementaires,
            'posts_count' => $this->whenCounted('rawContents'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
