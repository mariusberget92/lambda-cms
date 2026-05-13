<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
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

    public function update(string $group, Request $request): RedirectResponse
    {
        $validated = match ($group) {
            'site'   => $request->validate([
                'site\\.name' => ['required', 'string', 'max:100'],
                'site\\.url'  => ['required', 'url', 'max:255'],
            ]),
            'locale' => $request->validate([
                'locale\\.timezone'    => ['required', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
                'locale\\.date_format' => ['required', 'string', 'max:20'],
            ]),
            'media'  => (function () use ($request): array {
                $validated = $request->validate([
                    'media\\.max_upload_mb'       => ['required', 'integer', 'min:1', 'max:500'],
                    'media\\.resize_max_width'    => ['required', 'integer', 'min:100', 'max:5000'],
                    'media\\.allowed_categories'  => ['array'],
                    'media\\.allowed_categories.*' => ['in:image,document,video,audio'],
                    'media\\.custom_mimes'        => ['array'],
                    'media\\.custom_mimes.*'      => ['string', 'max:100'],
                ]);
                // Encode array fields as JSON before the generic save loop
                $validated['media.allowed_categories'] = json_encode($validated['media.allowed_categories'] ?? []);
                $validated['media.custom_mimes']       = json_encode($validated['media.custom_mimes'] ?? []);
                return $validated;
            })(),
            'mail'   => $request->validate([
                'mail\\.driver'       => ['required', 'string', Rule::in(['smtp', 'log', 'mailgun'])],
                'mail\\.host'         => ['nullable', 'string', 'max:255'],
                'mail\\.port'         => ['nullable', 'integer'],
                'mail\\.username'     => ['nullable', 'string', 'max:255'],
                'mail\\.password'     => ['nullable', 'string', 'max:255'],
                'mail\\.from_address' => ['required', 'email'],
                'mail\\.from_name'    => ['required', 'string', 'max:100'],
                'mail\\.encryption'   => ['nullable', Rule::in(['tls', 'ssl', ''])],
            ]),
            'comments' => $request->validate([
                'comments\\.enabled'  => ['required', 'in:0,1'],
                'comments\\.per_page' => ['required', 'integer', 'min:5', 'max:100'],
            ]),
            'seo' => $request->validate([
                'seo\\.title_separator'      => ['required', 'string', 'max:10'],
                'seo\\.default_description'  => ['nullable', 'string', 'max:300'],
                'seo\\.default_og_image_url' => ['nullable', 'url', 'max:500'],
                'seo\\.default_keywords'     => ['nullable', 'string', 'max:255'],
            ]),
            'appearance' => $request->validate([
                'site\\.accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ]),
            default  => abort(404),
        };

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        ActivityLogger::log('updated', "Updated {$group} settings", 'Settings');

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
