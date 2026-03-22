<?php

namespace App\Models;

use App\Models\Concerns\HasRevisions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasRevisions;

    protected $fillable = [
        'user_id', 'type', 'title', 'status', 'blocks',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected $casts = ['blocks' => 'array'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /** Return the one published template for a given type, or null. */
    public static function activeFor(string $type): ?self
    {
        return static::published()->where('type', $type)->first();
    }

    public function autosaves()
    {
        return $this->morphMany(\App\Models\Autosave::class, 'autosaveable');
    }

    public function autosave(int $userId): ?\App\Models\Autosave
    {
        return $this->autosaves()->where('user_id', $userId)->first();
    }
}
