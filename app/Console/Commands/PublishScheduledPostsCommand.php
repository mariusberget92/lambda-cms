<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPostsCommand extends Command
{
    protected $signature   = 'posts:publish-scheduled';
    protected $description = 'Publish scheduled posts whose publish date has passed.';

    public function handle(): void
    {
        $posts = Post::scheduled()
            ->where('published_at', '<=', now())
            ->get();

        $posts->each(fn (Post $post) => $post->update(['status' => 'published']));

        $this->info("Published {$posts->count()} scheduled post(s).");
    }
}
