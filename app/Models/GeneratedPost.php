<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedPost extends Model
{
    protected $fillable = [
        'raw_content_id',
        'hook_propose',
        'body_points',
        'technical_readability_score',
        'suggested_hashtags',
        'tone_compliance_justification',
        'payload_brut',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'body_points' => 'array',
            'suggested_hashtags' => 'array',
            'payload_brut' => 'array',
        ];
    }

    public function rawContent(): BelongsTo
    {
        return $this->belongsTo(RawContent::class);
    }
}
