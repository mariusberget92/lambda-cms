<?php

namespace App\Models\Concerns;

use App\Models\Revision;

trait HasRevisions
{
    public function revisions()
    {
        return $this->morphMany(Revision::class, 'revisable');
    }

    /**
     * Save a snapshot of current DB attributes as a new revision,
     * then prune to keep only the 25 most recent.
     */
    public function saveRevision(int $userId): void
    {
        $payload = collect($this->attributesToArray())
            ->except(['id', 'created_at', 'updated_at'])
            ->toArray();

        $this->revisions()->create([
            'user_id'    => $userId,
            'payload'    => $payload,
            'created_at' => now(),
        ]);

        $this->pruneRevisions();
    }

    /**
     * Delete revisions beyond the 25 most recent (by id DESC).
     */
    public function pruneRevisions(): void
    {
        $keepIds = $this->revisions()
            ->orderByDesc('id')
            ->limit(25)
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return;
        }

        $this->revisions()->whereNotIn('id', $keepIds)->delete();
    }
}
