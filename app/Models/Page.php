<?php

namespace App\Models;

use App\Models\Concerns\HasRevisions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;
    use HasRevisions;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $page) {
            if (empty($page->preview_token)) {
                $page->preview_token = Str::random(64);
            }
        });
    }

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'status',
        'blocks',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'custom_js',
        'preview_token',
    ];

    protected $casts = [
        'blocks' => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public static function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug     = Str::slug($title);
        $original = $slug;
        $count    = 1;

        while (
            static::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }
}
