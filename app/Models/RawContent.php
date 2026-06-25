<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RawContent extends Model
{
    protected $fillable = [
        'user_id',
        'blueprint_id',
        'contenu_brut',
        'statut',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }

    public function generatedPost(): HasOne
    {
        return $this->hasOne(GeneratedPost::class);
    }
}
