<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── Installer ─────────────────────────────────────────────────────────────────
Route::middleware('not_installed')->prefix('install')->group(function () {
    Route::get('/',        fn () => redirect('/install/database'));
    Route::get('/database', [InstallController::class, 'showDatabase'])->name('install.database');
    Route::post('/database', [InstallController::class, 'database']);
    Route::get('/site',     [InstallController::class, 'showSite'])->name('install.site');
    Route::post('/site',    [InstallController::class, 'site']);
    Route::get('/admin',    [InstallController::class, 'showAdmin'])->name('install.admin');
    Route::post('/admin',   [InstallController::class, 'admin']);
    Route::get('/mail',     [InstallController::class, 'showMail'])->name('install.mail');
    Route::post('/mail',    [InstallController::class, 'mail']);
});

// ── All routes below require the app to be installed ─────────────────────────
Route::middleware('installed')->group(function () {

    // ── Public blog ──────────────────────────────────────────────────────────
    Route::get('/',             [BlogController::class, 'index'])->name('home');
    Route::get('/blog/{slug}',  [BlogController::class, 'show'])->name('blog.show');

    // Comment submission (public, rate-limited)
    Route::post('/blog/{post:slug}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:comments')
        ->name('comments.store');

    // ── Guest-only ───────────────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('/login',  [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('auth.login');

        Route::get('/forgot-password',  [ForgotPasswordController::class, 'show'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->name('password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
        Route::post('/reset-password',        [ResetPasswordController::class, 'reset'])->name('password.update');
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
            return back()->with('status', 'verification-link-sent');
        })->middleware('throttle:6,1')->name('verification.send');
    });

    // ── Auth + verified ──────────────────────────────────────────────────────
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('posts',      PostController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('tags',       TagController::class)->except(['show']);

        Route::get('/profile',           [ProfileController::class, 'show'])->name('profile');
        Route::post('/profile/info',     [ProfileController::class, 'updateInfo'])->name('profile.info');
        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/profile/avatar',   [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

        // Media library
        Route::get('/media',           [MediaController::class, 'index'])->name('media.index');
        Route::post('/media',          [MediaController::class, 'store'])->name('media.store');
        Route::patch('/media/{media}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('/media/bulk',   [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    });

    // ── Auth + verified + administrator role ─────────────────────────────────
    Route::middleware(['auth', 'verified', 'role:administrator'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);

        Route::get('/comments',                     [CommentController::class, 'index'])->name('comments.index');
        Route::patch('/comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
        Route::patch('/comments/{comment}/reject',  [CommentController::class, 'reject'])->name('comments.reject');
        Route::delete('/comments/{comment}',        [CommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('/comments/bulk',               [CommentController::class, 'bulk'])->name('comments.bulk');

        Route::get('/settings',             [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/{group}',     [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
    });
});
