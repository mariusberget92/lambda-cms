<?php

namespace App\Jobs;

use App\Mail\NewCommentNotification;
use App\Models\Comment;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewCommentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Comment $comment) {}

    public function handle(): void
    {
        try {
            $adminEmail = Setting::get('mail.from_address', config('mail.from.address'));

            if (empty($adminEmail)) {
                Log::warning('SendNewCommentNotification: no admin email configured, skipping.');
                return;
            }

            Mail::to($adminEmail)->send(new NewCommentNotification($this->comment));
        } catch (\Throwable $e) {
            Log::error('SendNewCommentNotification failed', ['exception' => $e->getMessage()]);
        }
    }
}
