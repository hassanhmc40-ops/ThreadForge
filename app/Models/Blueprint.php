<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blueprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'tone',
        'max_hashtags',
        'max_characters',
        'regles_supplementaires',
    ];

    protected function casts(): array
    {
        return [
            'max_hashtags' => 'integer',
            'max_characters' => 'integer',
            'regles_supplementaires' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rawContents(): HasMany
    {
        return $this->hasMany(RawContent::class);
    }

    public function generatedPosts(): HasMany
    {
        return $this->hasManyThrough(GeneratedPost::class, RawContent::class);
    }
}
