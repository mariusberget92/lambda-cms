<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavItem extends Model
{
    protected $fillable = ['type', 'label', 'url', 'page_id', 'sort_order'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * The resolved public URL for this nav item.
     */
    public function getResolvedUrl(): ?string
    {
        if ($this->type === 'page') {
            return $this->page ? '/' . $this->page->slug : null;
        }

        return $this->url;
    }
}
