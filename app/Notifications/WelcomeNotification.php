<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Services\TemplateMailer;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class WelcomeNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable)
    {
        return app(TemplateMailer::class)->build('welcome', [
            'user_name' => $notifiable->name,
            'reset_url' => $this->buildPasswordResetUrl($notifiable),
            'verification_url' => $this->buildVerificationUrl($notifiable),
            'site_name' => Setting::get('site.name', config('app.name')),
        ])->to($notifiable->email);
    }

    protected function buildVerificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addDay(),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    protected function buildPasswordResetUrl(object $notifiable): string
    {
        $token = Password::broker()->createToken($notifiable);

        return route('password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
