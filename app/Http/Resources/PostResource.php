<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hook_propose' => $this->hook_propose,
            'body_points' => $this->body_points,
            'technical_readability_score' => $this->technical_readability_score,
            'suggested_hashtags' => $this->suggested_hashtags,
            'tone_compliance_justification' => $this->tone_compliance_justification,
            'statut' => $this->statut,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
