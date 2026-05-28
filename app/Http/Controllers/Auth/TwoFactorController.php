<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function enable(Request $request): JsonResponse
    {
        $user   = $request->user();
        $secret = $this->google2fa->generateSecretKey();
        $qrUri  = $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);

        $user->update([
            'two_factor_secret'         => encrypt($secret),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ]);

        return response()->json(['qr_uri' => $qrUri, 'secret' => $secret]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user   = $request->user();
        $secret = $user->two_factor_secret ? decrypt($user->two_factor_secret) : null;

        if (! $secret) {
            return response()->json(['message' => '2FA setup not initiated.'], 422);
        }

        if (! $this->google2fa->verifyKey($secret, $request->input('code'))) {
            return response()->json(['message' => 'Invalid code. Please try again.'], 422);
        }

        $codes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes->toArray())),
            'two_factor_confirmed_at'   => now(),
        ]);

        return response()->json(['recovery_codes' => $codes->toArray()]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->user()->update([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ]);

        return response()->json(['disabled' => true]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return response()->json(['message' => '2FA is not enabled.'], 422);
        }

        return response()->json([
            'recovery_codes' => json_decode(decrypt($user->two_factor_recovery_codes), true),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return response()->json(['message' => '2FA is not enabled.'], 422);
        }

        $codes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes->toArray())),
        ]);

        return response()->json(['recovery_codes' => $codes->toArray()]);
    }

    private function generateRecoveryCodes(): Collection
    {
        return collect(range(1, 8))->map(
            fn () => strtoupper(Str::random(5)) . '-' . strtoupper(Str::random(5))
        );
    }
}
