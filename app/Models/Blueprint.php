<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blueprint extends Model
{
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
}
