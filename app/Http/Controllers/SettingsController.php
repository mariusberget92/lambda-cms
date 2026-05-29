<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Mail\TestMail;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $settings = Setting::all()->keyBy('key')->map(function ($s) {
            // Mask the mail password — it is write-only from the UI perspective.
            // The frontend password field is always empty on load; saving a blank
            // value is handled by the update() action which skips empty passwords.
            if ($s->key === 'mail.password') {
                return '';
            }

            return $s->value;
        });

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(string $group, UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($group === 'media') {
            $validated['media.allowed_categories'] = json_encode($validated['media.allowed_categories'] ?? []);
            $validated['media.custom_mimes']       = json_encode($validated['media.custom_mimes'] ?? []);
        }

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return back()->with('status', 'Settings saved.');
    }

    public function testEmail(Request $request): RedirectResponse
    {
        // Apply current mail settings at runtime
        $driver = Setting::get('mail.driver', 'log');
        Config::set('mail.default', $driver);
        Config::set('mail.mailers.smtp.host',       Setting::get('mail.host', ''));
        Config::set('mail.mailers.smtp.port',       Setting::get('mail.port', 587));
        Config::set('mail.mailers.smtp.username',   Setting::get('mail.username', ''));
        Config::set('mail.mailers.smtp.password',   Setting::get('mail.password', ''));
        Config::set('mail.mailers.smtp.encryption', Setting::get('mail.encryption', 'tls') ?: null);
        Config::set('mail.from.address', Setting::get('mail.from_address', ''));
        Config::set('mail.from.name',    Setting::get('mail.from_name', ''));

        try {
            Mail::to($request->user()->email)->send(new TestMail());
            return back()->with('mail_status', 'Test email sent successfully to ' . $request->user()->email);
        } catch (\Throwable $e) {
            Log::error('Settings test email failed', ['exception' => $e->getMessage()]);
            return back()->with('mail_error', 'Failed to send test email. Please check your mail configuration and try again.');
        }
    }
}
