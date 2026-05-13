<?php

namespace App\Models;

use App\Models\Media;
use App\Models\Setting;
use App\Models\Concerns\HasRevisions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;
    use HasRevisions;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $post) {
            if (empty($post->preview_token)) {
                $post->preview_token = Str::random(64);
            }
        });
    }

    protected $fillable = [
        "user_id",
        "featured_image_id",
        "title",
        "slug",
        "excerpt",
        "body",
        "status",
        "featured",
        "published_at",
        "comments_enabled",
        "use_block_editor",
        "blocks",
        "meta_title",
        "meta_description",
        "meta_keywords",
    ];

    protected $casts = [
        "published_at"     => "datetime",
        "comments_enabled" => "boolean",
        "use_block_editor" => "boolean",
        "featured"         => "boolean",
        "blocks"           => "array",
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
        return $query->where('status', 'published')
                     ->where(function ($q) {
                         $q->whereNull('published_at')
                           ->orWhere('published_at', '<=', now());
                     });
    }

    public function scopeDraft($query)
    {
        return $query->where("status", "draft");
    }

    public function scopeScheduled($query)
    {
        return $query->where("status", "scheduled");
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

    public function isScheduled(): bool
    {
        return $this->status === "scheduled";
    }

    public function commentsOpen(): bool
    {
        if (! Setting::get('comments.enabled', true)) {
            return false;
        }
        return (bool) $this->comments_enabled;
    }

    public function readingTime(): int
    {
        if ($this->use_block_editor && is_array($this->blocks)) {
            $text = $this->extractBlocksText($this->blocks);
        } else {
            $text = strip_tags((string) $this->body);
        }

        $words = str_word_count($text);
        return max(1, (int) ceil($words / 200));
    }

    private function extractBlocksText(array $blocks): string
    {
        $parts = [];
        foreach ($blocks as $block) {
            foreach ($block['data'] ?? [] as $value) {
                if (is_string($value)) {
                    $parts[] = strip_tags($value);
                } elseif (is_array($value)) {
                    $parts[] = $this->extractBlocksText($value);
                }
            }
            if (! empty($block['children'])) {
                $parts[] = $this->extractBlocksText($block['children']);
            }
        }
        return implode(' ', $parts);
    }
}
