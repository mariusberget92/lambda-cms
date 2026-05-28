<?php

namespace App\Services;

use App\Models\Setting;

class LicenseService
{
    public function isPro(): bool
    {
        return true;
    }

    /**
     * Activate a pro license key.
     * Format: XXXXX-XXXXX-XXXXX-XXXXX (alphanumeric, case-insensitive)
     * Production note: replace the stub below with a call to the licensing API.
     */
    public function activate(string $key): array
    {
        $key = strtoupper(trim($key));

        if (! preg_match('/^[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $key)) {
            return [
                'success' => false,
                'message' => 'Invalid license key format. Expected XXXXX-XXXXX-XXXXX-XXXXX.',
            ];
        }

        // TODO: validate against https://lambdacms.io/api/license/validate
        // For now any correctly-formatted key is accepted.

        Setting::set('license.key',          $key);
        Setting::set('license.status',       'active');
        Setting::set('license.activated_at', now()->toIso8601String());

        return [
            'success' => true,
            'message' => 'Pro license activated successfully.',
        ];
    }

    public function deactivate(): void
    {
        Setting::set('license.key',          '');
        Setting::set('license.status',       'inactive');
        Setting::set('license.activated_at', '');
    }

    public function getInfo(): array
    {
        $key         = Setting::get('license.key', '');
        $status      = Setting::get('license.status', 'inactive');
        $activatedAt = Setting::get('license.activated_at', '');

        return [
            'key'          => $key ? substr($key, 0, 5) . '-****-****-****' : null,
            'status'       => $status,
            'is_pro'       => $status === 'active',
            'activated_at' => $activatedAt ?: null,
        ];
    }
}
