<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;

class ExpirePostsCommand extends Command
{
    protected $signature   = 'posts:expire';
    protected $description = 'Revert published posts to draft when their expiry date has passed.';

    public function handle(): void
    {
        $posts = Post::where('status', 'published')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($posts as $post) {
            $post->update(['status' => 'draft']);
            ActivityLogger::log(
                'updated',
                "Post '{$post->title}' auto-expired and reverted to draft",
                'Post',
                $post->id
            );
        }

        $this->info("Expired {$posts->count()} post(s).");
    }
}
