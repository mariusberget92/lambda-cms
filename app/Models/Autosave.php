<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Autosave extends Model
{
    protected $fillable = [
        'autosaveable_type',
        'autosaveable_id',
        'user_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function autosaveable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
