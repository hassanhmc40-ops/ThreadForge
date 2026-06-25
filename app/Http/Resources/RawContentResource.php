<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RawContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contenu_brut' => $this->contenu_brut,
            'statut' => $this->statut,
            'blueprint' => $this->relationLoaded('blueprint') && $this->blueprint
                ? new BlueprintResource($this->blueprint)
                : null,
            'generated_post' => $this->relationLoaded('generatedPost') && $this->generatedPost
                ? new PostResource($this->generatedPost)
                : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
