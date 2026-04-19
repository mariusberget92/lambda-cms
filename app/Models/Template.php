<?php

namespace App\Models;

use App\Models\Concerns\HasRevisions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasFactory;
    use HasRevisions;

    protected $fillable = [
        'user_id',
        'type',
        'loop_source',
        'is_system',
        'title',
        'status',
        'blocks',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'blocks'    => 'array',
        'is_system' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function autosaves()
    {
        return $this->morphMany(\App\Models\Autosave::class, 'autosaveable');
    }

    public function autosave(int $userId): ?\App\Models\Autosave
    {
        return $this->autosaves()->where('user_id', $userId)->first();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Return the one published template for a given type, or null. */
    public static function activeFor(string $type): ?self
    {
        return static::published()->where('type', $type)->first();
    }
}
