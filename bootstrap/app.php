<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Exceptions\UnauthorizedException;

// Auto-bootstrap: ensure the app can boot cleanly before installation is complete.
(function () {
    $envPath      = dirname(__DIR__) . '/.env';
    $examplePath  = dirname(__DIR__) . '/.env.example';
    $installedPath = dirname(__DIR__) . '/storage/app/installed';

    // If no .env exists, copy from .env.example and generate an APP_KEY.
    if (! file_exists($envPath) && file_exists($examplePath)) {
        copy($examplePath, $envPath);

        $key      = 'base64:' . base64_encode(random_bytes(32));
        $contents = file_get_contents($envPath);
        $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $contents);
        file_put_contents($envPath, $contents);
    }

    // If the app has not been installed yet, force file-based session and cache
    // so the installer can load even if a previous failed install wrote
    // SESSION_DRIVER=database / CACHE_STORE=database to .env before migrations ran.
    if (! file_exists($installedPath) && file_exists($envPath)) {
        $contents = file_get_contents($envPath);
        $contents = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', $contents);
        $contents = preg_replace('/^CACHE_STORE=.*/m',    'CACHE_STORE=file',    $contents);
        file_put_contents($envPath, $contents);
    }
})();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\BootstrapSettings::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\TrackLastSeen::class,
        ]);

        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'installed'          => \App\Http\Middleware\EnsureInstalled::class,
            'not_installed'      => \App\Http\Middleware\EnsureNotInstalled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (UnauthorizedException $e, \Illuminate\Http\Request $request) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access that page.');
        });
    })->create();
