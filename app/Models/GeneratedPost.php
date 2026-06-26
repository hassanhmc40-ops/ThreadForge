<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneratedPost extends Model
{
    use HasFactory;

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

    public function conversations(): HasMany
    {
        return $this->hasMany(\App\Models\AgentConversation::class, 'generated_post_id');
    }
}
