<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function show(Request $request)
    {
        if (! $request->session()->has('two_factor_auth_user_id')) {
            return redirect()->route('auth.login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function verify(TwoFactorRequest $request)
    {
        $userId = $request->session()->get('two_factor_auth_user_id');

        if (! $userId || ! ($user = User::find($userId))) {
            $request->session()->forget(['two_factor_auth_user_id', 'two_factor_auth_remember']);
            return redirect()->route('auth.login');
        }

        $code     = trim($request->input('code'));
        $verified = false;

        // Try TOTP (6 digits)
        if (preg_match('/^\d{6}$/', $code) && $user->two_factor_secret) {
            $verified = $this->google2fa->verifyKey(decrypt($user->two_factor_secret), $code);
        }

        // Try recovery code
        if (! $verified) {
            $verified = $this->consumeRecoveryCode($user, $code);
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'The code you entered is invalid.']);
        }

        $remember = $request->session()->pull('two_factor_auth_remember', false);
        $request->session()->forget('two_factor_auth_user_id');

        Auth::loginUsingId($user->id, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function consumeRecoveryCode(User $user, string $input): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $codes    = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $normalize = fn (string $c) => strtolower(str_replace(['-', ' '], '', $c));
        $needle   = $normalize($input);

        foreach ($codes as $index => $stored) {
            if (hash_equals($normalize($stored), $needle)) {
                array_splice($codes, $index, 1);
                $user->update([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
                ]);
                return true;
            }
        }

        return false;
    }
}
