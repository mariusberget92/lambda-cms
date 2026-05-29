<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class WelcomeNotification extends Notification
{
    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->buildVerificationUrl($notifiable);
        $resetUrl        = $this->buildPasswordResetUrl($notifiable);

        return (new MailMessage)
            ->subject('Welcome to Lambda CMS — Set up your account')
            ->greeting("Hello, {$notifiable->name}!")
            ->line('An account has been created for you on **Lambda CMS**.')
            ->line('Please complete two steps to activate your account:')
            ->line('**Step 1 — Set your password:**')
            ->action('Set Your Password', $resetUrl)
            ->line('**Step 2 — Verify your email address:**')
            ->line('After setting your password and logging in, verify your email with this link:')
            ->line('[Verify Email Address](' . $verificationUrl . ')')
            ->line('Both links expire in 24 hours.')
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }

    /**
     * Build a signed email verification URL for the given user.
     */
    protected function buildVerificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addDay(),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Build a password reset URL for the given user.
     */
    protected function buildPasswordResetUrl(object $notifiable): string
    {
        $token = Password::broker()->createToken($notifiable);

        return route('password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
