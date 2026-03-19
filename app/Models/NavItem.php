<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'label',
        'url',
        'page_id',
        'sort_order',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getResolvedUrlAttribute(): string
    {
        if ($this->type === 'custom') {
            return $this->url ?? '';
        }

        return $this->page ? "/{$this->page->slug}" : '';
    }
}
