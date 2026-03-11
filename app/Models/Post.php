<?php

namespace App\Models;

use App\Models\Media;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "featured_image_id",
        "title",
        "slug",
        "excerpt",
        "body",
        "status",
        "published_at",
        "comments_enabled",
        "meta_title",
        "meta_description",
        "meta_keywords",
    ];

    protected $casts = [
        "published_at"     => "datetime",
        "comments_enabled" => "boolean",
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where("status", "published");
    }

    public function scopeDraft($query)
    {
        return $query->where("status", "draft");
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where("title", "like", "%{$term}%")
              ->orWhere("excerpt", "like", "%{$term}%");
        });
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public static function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (
            static::where("slug", $slug)
                ->when($excludeId, fn ($q) => $q->where("id", "!=", $excludeId))
                ->exists()
        ) {
            $slug = $original . "-" . $count++;
        }

        return $slug;
    }

    public function isPublished(): bool
    {
        return $this->status === "published";
    }

    public function commentsOpen(): bool
    {
        if (! Setting::get('comments.enabled', true)) {
            return false;
        }
        return (bool) $this->comments_enabled;
    }
}
