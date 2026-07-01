<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Observers\PageObserver;
use App\Observers\PostObserver;
use App\Services\TemplateMailer;
use App\Services\TemplateResolver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TemplateResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('comments', function ($request) {
            return Limit::perMinute(1)->by($request->ip());
        });

        Post::observe(PostObserver::class);
        Page::observe(PageObserver::class);

        $this->configureMailTemplates();
    }

    protected function configureMailTemplates(): void
    {
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $mailer = app(TemplateMailer::class);

            return $mailer->build('password-reset', [
                'user_name' => $notifiable->name,
                'reset_url' => url(route('password.reset', ['token' => $token, 'email' => $notifiable->email], false)),
                'site_name' => Setting::get('site.name', config('app.name')),
            ])->to($notifiable->email);
        });

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $mailer = app(TemplateMailer::class);

            return $mailer->build('email-verification', [
                'user_name' => $notifiable->name,
                'verification_url' => $url,
                'site_name' => Setting::get('site.name', config('app.name')),
            ])->to($notifiable->email);
        });
    }
}
