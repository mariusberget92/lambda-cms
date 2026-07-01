<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\AutosaveController;
use App\Http\Controllers\BanController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CallListController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CrmExportController;
use App\Http\Controllers\CrmImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── Installer ─────────────────────────────────────────────────────────────────
Route::middleware('not_installed')->prefix('install')->group(function () {
    Route::get('/', fn () => redirect('/install/database'));
    Route::get('/database', [InstallController::class, 'showDatabase'])->name('install.database');
    Route::post('/database', [InstallController::class, 'database']);
    Route::get('/site', [InstallController::class, 'showSite'])->name('install.site');
    Route::post('/site', [InstallController::class, 'site']);
    Route::get('/admin', [InstallController::class, 'showAdmin'])->name('install.admin');
    Route::post('/admin', [InstallController::class, 'admin']);
    Route::get('/mail', [InstallController::class, 'showMail'])->name('install.mail');
    Route::post('/mail', [InstallController::class, 'mail']);
});

// ── All routes below require the app to be installed ─────────────────────────
Route::middleware('installed')->group(function () {

    // ── Public blog ──────────────────────────────────────────────────────────
    Route::get('/', [BlogController::class, 'index'])->name('home');
    Route::get('/blog/{post:slug}/comments', [BlogController::class, 'comments'])->name('blog.comments');
    Route::get('/blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('/blog/tag/{tag:slug}', [BlogController::class, 'tag'])->name('blog.tag');
    Route::get('/feed', FeedController::class)->name('feed');
    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

    // Comment submission (public, rate-limited)
    Route::post('/blog/{post:slug}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:comments')
        ->name('comments.store');

    // Public subscribe / unsubscribe
    Route::post('/subscribe', [SubscribeController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('subscribe');
    Route::get('/subscribe', fn () => view('subscribe'))->name('subscribe.form');
    Route::get('/unsubscribe/{token}', [SubscribeController::class, 'unsubscribe'])->name('unsubscribe');

    // ── Preview (public, token-based) ────────────────────────────────────────
    Route::get('/preview/posts/{token}', [PreviewController::class, 'post'])->name('preview.post');
    Route::get('/preview/pages/{token}', [PreviewController::class, 'page'])->name('preview.page');

    // ── Guest-only ───────────────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('auth.login');

        Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->name('password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

        Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
        Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])->name('two-factor.verify');
    });

    // ── Auth only (email verification + logout) ──────────────────────────────
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

        Route::get('/email/verify', function () {
            return inertia('Auth/VerifyEmail');
        })->name('verification.notice');

        Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
            $request->fulfill();

            return redirect()->route('dashboard')->with('status', 'Email verified! Welcome to Lambda CMS.');
        })->middleware('signed')->name('verification.verify');

        Route::post('/email/verification-notification', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();

            return back()->with('status', 'A new verification link has been sent to your email address.');
        })->middleware('throttle:6,1')->name('verification.send');
    });

    // ── Auth + verified ──────────────────────────────────────────────────────
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::post('/posts/bulk', [PostController::class, 'bulk'])->name('posts.bulk');
        Route::resource('posts', PostController::class)->except(['show']);

        // Autosave
        Route::post('/posts/{post}/autosave', [AutosaveController::class, 'storePost'])->name('posts.autosave');
        Route::delete('/posts/{post}/autosave', [AutosaveController::class, 'destroyPost'])->name('posts.autosave.destroy');

        // Revisions
        Route::get('/posts/{post}/revisions', [RevisionController::class, 'indexPost'])->name('posts.revisions');
        Route::get('/revisions/{revision}/restore', [RevisionController::class, 'restore'])->name('revisions.restore');
        Route::post('/categories/bulk', [CategoryController::class, 'bulk'])->name('categories.bulk');
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::post('/tags/bulk', [TagController::class, 'bulk'])->name('tags.bulk');
        Route::resource('tags', TagController::class)->except(['show']);

        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
        Route::get('/calendar/data', [CalendarController::class, 'data'])->name('calendar.data');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::post('/profile/info', [ProfileController::class, 'updateInfo'])->name('profile.info');
        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

        Route::post('/profile/two-factor', [TwoFactorController::class, 'enable'])->name('profile.two-factor.enable');
        Route::post('/profile/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('profile.two-factor.confirm');
        Route::delete('/profile/two-factor', [TwoFactorController::class, 'disable'])->name('profile.two-factor.disable');
        Route::get('/profile/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('profile.two-factor.recovery-codes');
        Route::post('/profile/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('profile.two-factor.regenerate-recovery-codes');

        // ── CRM ──────────────────────────────────────────────────────────────
        Route::post('/contacts/bulk', [ContactController::class, 'bulk'])->name('contacts.bulk');
        Route::resource('contacts', ContactController::class)->except(['show']);

        Route::post('/companies/bulk', [CompanyController::class, 'bulk'])->name('companies.bulk');
        Route::resource('companies', CompanyController::class)->except(['show']);

        Route::post('/deals/bulk', [DealController::class, 'bulk'])->name('deals.bulk');
        Route::resource('deals', DealController::class)->except(['show']);

        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

        // Call Lists
        Route::post('/call-lists/bulk', [CallListController::class, 'bulk'])->name('call-lists.bulk');
        Route::resource('call-lists', CallListController::class)->except(['show']);
        Route::get('/call-lists/{call_list}/work', [CallListController::class, 'work'])->name('call-lists.work');
        Route::post('/call-lists/{call_list}/contacts', [CallListController::class, 'addContacts'])->name('call-lists.add-contacts');
        Route::post('/call-lists/{call_list}/contacts/remove', [CallListController::class, 'removeContacts'])->name('call-lists.remove-contacts');
        Route::put('/call-lists/{call_list}/contacts/{contact}/status', [CallListController::class, 'updateContactStatus'])->name('call-lists.update-contact-status');

        // CRM CSV Export/Import
        Route::get('/crm/export', [CrmExportController::class, 'index'])->name('crm-export.index');
        Route::get('/crm/export/download', [CrmExportController::class, 'download'])->name('crm-export.download');
        Route::get('/crm/import', [CrmImportController::class, 'index'])->name('crm-import.index');
        Route::post('/crm/import/preview', [CrmImportController::class, 'preview'])->name('crm-import.preview');
        Route::post('/crm/import', [CrmImportController::class, 'store'])->name('crm-import.store');

        // Subscribers
        Route::post('/subscribers/bulk', [SubscriberController::class, 'bulk'])->name('subscribers.bulk');
        Route::get('/subscribers/export', [SubscriberController::class, 'export'])->name('subscribers.export');
        Route::get('/subscribers/import', [SubscriberController::class, 'importForm'])->name('subscribers.import');
        Route::post('/subscribers/import/preview', [SubscriberController::class, 'importPreview'])->name('subscribers.import.preview');
        Route::post('/subscribers/import', [SubscriberController::class, 'importStore'])->name('subscribers.import.store');
        Route::get('/subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');
        Route::delete('/subscribers/{subscriber}', [SubscriberController::class, 'destroy'])->name('subscribers.destroy');

        // Campaigns
        Route::post('/campaigns/bulk', [CampaignController::class, 'bulk'])->name('campaigns.bulk');
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
        Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
        Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
        Route::post('/campaigns/{campaign}/schedule', [CampaignController::class, 'schedule'])->name('campaigns.schedule');
        Route::post('/campaigns/{campaign}/unschedule', [CampaignController::class, 'unschedule'])->name('campaigns.unschedule');
        Route::get('/campaigns/{campaign}/report', [CampaignController::class, 'report'])->name('campaigns.report');

        // Media library
        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::get('/media/{media}/usage', [MediaController::class, 'usage'])->name('media.usage');
        Route::post('/media/{media}/replace', [MediaController::class, 'replace'])->name('media.replace');
        Route::patch('/media/{media}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('/media/bulk', [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    });

    // ── Auth + verified + administrator role ─────────────────────────────────
    Route::middleware(['auth', 'verified', 'role:administrator'])->group(function () {
        // Email Templates
        Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('/email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::post('/email-templates/{emailTemplate}/reset', [EmailTemplateController::class, 'reset'])->name('email-templates.reset');
        Route::post('/email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');

        Route::post('/pages/bulk', [PageController::class, 'bulk'])->name('pages.bulk');
        Route::resource('pages', PageController::class)->except(['show']);
        Route::post('/pages/{page}/autosave', [AutosaveController::class, 'storePage'])->name('pages.autosave');
        Route::delete('/pages/{page}/autosave', [AutosaveController::class, 'destroyPage'])->name('pages.autosave.destroy');
        Route::get('/pages/{page}/revisions', [RevisionController::class, 'indexPage'])->name('pages.revisions');

        Route::post('/templates/bulk', [TemplateController::class, 'bulk'])->name('templates.bulk');
        Route::resource('templates', TemplateController::class)->except(['show']);
        Route::post('/templates/{template}/autosave', [AutosaveController::class, 'storeTemplate'])->name('templates.autosave');
        Route::delete('/templates/{template}/autosave', [AutosaveController::class, 'destroyTemplate'])->name('templates.autosave.destroy');
        Route::get('/templates/{template}/revisions', [RevisionController::class, 'indexTemplate'])->name('templates.revisions');

        Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
        Route::resource('users', UserController::class)->except(['show']);

        Route::post('/users/{user}/ban', [BanController::class, 'ban'])->name('users.ban');
        Route::delete('/users/{user}/ban', [BanController::class, 'unban'])->name('users.unban');

        Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');
        Route::patch('/comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
        Route::patch('/comments/{comment}/reject', [CommentController::class, 'reject'])->name('comments.reject');
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('/comments/bulk', [CommentController::class, 'bulk'])->name('comments.bulk');
        Route::post('/comments/{comment}/reply', [CommentController::class, 'reply'])->name('comments.reply');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
        Route::get('/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
        Route::post('/webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
        Route::put('/webhooks/{webhook}', [WebhookController::class, 'update'])->name('webhooks.update');
        Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');

        Route::get('/export', [ExportController::class, 'index'])->name('export.index');
        Route::get('/export/download', [ExportController::class, 'download'])->name('export.download');
        Route::get('/import', [ImportController::class, 'index'])->name('import.index');
        Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
        Route::post('/import', [ImportController::class, 'store'])->name('import.store');

    });
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // ── Public custom pages (catch-all — must be last inside this group) ─────
    Route::get('/{slug}', [PublicPageController::class, 'show'])
        ->where('slug', '^(?!login|logout|dashboard|blog|feed|sitemap\.xml|posts|categories|tags|users|profile|settings|media|comments|pages|templates|calendar|password|register|verify|install|email|forgot-password|reset-password|search|export|import|navigation|webhooks|contacts|companies|deals|activities|call-lists|crm|email-templates|subscribers|campaigns|subscribe|unsubscribe).*$')
        ->name('pages.show');

});
