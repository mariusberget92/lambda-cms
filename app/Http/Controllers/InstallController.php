<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstallAdminRequest;
use App\Http\Requests\InstallDatabaseRequest;
use App\Http\Requests\InstallMailRequest;
use App\Http\Requests\InstallSiteRequest;
use App\Models\User;
use Database\Seeders\LambdaContentSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use PDO;
use PDOException;

class InstallController extends Controller
{
    // ── Show methods ──────────────────────────────────────────────────────────

    public function showDatabase(): Response
    {
        return Inertia::render('Install/Database', ['step' => 1]);
    }

    public function showSite(Request $request): Response
    {
        return Inertia::render('Install/Site', [
            'step' => 2,
            'siteUrl' => $request->getSchemeAndHttpHost(),
        ]);
    }

    public function showAdmin(): Response
    {
        return Inertia::render('Install/Admin', ['step' => 3]);
    }

    public function showMail(): Response
    {
        return Inertia::render('Install/Mail', ['step' => 4]);
    }

    // ── POST handlers ─────────────────────────────────────────────────────────

    /**
     * Step 1 — Database configuration.
     */
    public function database(InstallDatabaseRequest $request): RedirectResponse
    {
        $driver = $request->input('driver', 'sqlite');

        if ($driver === 'mysql') {

            // Test connection
            try {
                $dsn = "mysql:host={$request->host};port={$request->port};dbname={$request->database}";
                new PDO($dsn, $request->username, $request->password ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
            } catch (PDOException $e) {
                return back()->withErrors(['database' => 'Could not connect to MySQL: '.$e->getMessage()]);
            }

            $this->writeEnv([
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $request->host,
                'DB_PORT' => (string) $request->port,
                'DB_DATABASE' => $request->database,
                'DB_USERNAME' => $request->username,
                'DB_PASSWORD' => $request->password ?? '',
                'DB_TABLE_PREFIX' => $request->prefix ?? '',
            ]);
        } else {

            // Ensure the SQLite database file exists before migrations run
            $sqlitePath = database_path('database.sqlite');
            if (! file_exists($sqlitePath)) {
                touch($sqlitePath);
            }

            $this->writeEnv([
                'DB_CONNECTION' => 'sqlite',
            ]);
        }

        Artisan::call('config:clear');

        return redirect('/install/site');
    }

    /**
     * Step 2 — Site configuration.
     */
    public function site(InstallSiteRequest $request): RedirectResponse
    {

        $this->writeEnv([
            'APP_NAME' => $request->site_name,
            'APP_URL' => $request->site_url,
        ]);

        Artisan::call('config:clear');

        return redirect('/install/admin');
    }

    /**
     * Step 3 — Admin account (stored in session only).
     */
    public function admin(InstallAdminRequest $request): RedirectResponse
    {

        $request->session()->put('install.admin', [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        return redirect('/install/mail');
    }

    /**
     * Step 4 — Mail configuration. Runs migrations, seeds, creates the admin
     * user, and completes the installation.
     */
    public function mail(InstallMailRequest $request): RedirectResponse
    {
        $mailer = $request->input('mailer', 'log');

        if ($mailer === 'smtp') {

            $this->writeEnv([
                'MAIL_MAILER' => 'smtp',
                'MAIL_HOST' => $request->host,
                'MAIL_PORT' => (string) $request->port,
                'MAIL_USERNAME' => $request->username,
                'MAIL_PASSWORD' => $request->password ?? '',
                'MAIL_FROM_ADDRESS' => $request->from_address,
                'MAIL_FROM_NAME' => $request->from_name,
            ]);
        } else {
            $this->writeEnv([
                'MAIL_MAILER' => 'log',
            ]);
        }

        Artisan::call('config:clear');

        // Run migrations (idempotent — safe to run even if partially applied)
        Artisan::call('migrate', ['--force' => true]);

        // Switch session and cache to database AFTER migrations have run
        $this->writeEnv([
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
        ]);

        Artisan::call('config:clear');

        // Retrieve admin data from session (stored in step 3).
        // Forget the key only after the transaction succeeds so a retry after a
        // partial failure can still read the credentials from session.
        $adminData = $request->session()->get('install.admin');

        $user = DB::transaction(function () use ($adminData) {
            Artisan::call('db:seed', ['--force' => true, '--class' => 'DatabaseSeeder']);

            // firstOrCreate so a retry after a partial failure doesn't violate the unique constraint
            $user = User::firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'name' => $adminData['name'],
                    'password' => Hash::make($adminData['password']),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole('administrator')) {
                $user->assignRole('administrator');
            }

            // Both seeders below require an administrator to exist and must
            // run after user creation — they return early otherwise.
            app(TemplateSeeder::class)->run();
            app(LambdaContentSeeder::class)->run();

            return $user;
        });

        $request->session()->forget('install.admin');

        // Mark as installed
        file_put_contents(storage_path('app/installed'), now()->toDateTimeString());

        // Log the admin in
        Auth::login($user);

        return redirect()->route('dashboard');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Write or update key=value pairs in the .env file.
     * Existing keys are replaced; new keys are appended.
     */
    private function writeEnv(array $values): void
    {
        $envPath = base_path('.env');
        $contents = file_exists($envPath) ? file_get_contents($envPath) : '';

        foreach ($values as $key => $value) {
            // Quote values that contain spaces
            $escaped = str_contains($value, ' ') ? "\"{$value}\"" : $value;
            $line = "{$key}={$escaped}";

            if (preg_match("/^{$key}=.*/m", $contents)) {
                // Replace existing key
                $contents = preg_replace("/^{$key}=.*/m", $line, $contents);
            } else {
                // Append new key
                $contents = rtrim($contents)."\n{$line}\n";
            }
        }

        file_put_contents($envPath, $contents);
    }
}
