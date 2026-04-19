<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show()
    {
        return Inertia::render('Profile/Index');
    }

    public function updateInfo(Request $request)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->user()->id),
            ],
        ]);

        $user = $request->user();
        $emailChanged = $validated['email'] !== $user->email;

        $user->update($validated);

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();
        }

        $message = $emailChanged
            ? 'Profile updated. Please check your inbox to verify your new email address.'
            : 'Profile updated.';

        return redirect()
            ->route('profile')
            ->with('status', $message);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password' => $request->password,
        ]);

        return redirect()
            ->route('profile')
            ->with('status', 'Password updated.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $user = $request->user();

        // Delete the previous avatar file if one exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $ext  = $request->file('avatar')->guessClientExtension() ?? 'jpg';
        $path = $request->file('avatar')->storeAs(
            'avatars',
            $user->id . '.' . $ext,
            'public'
        );

        $user->update(['avatar' => $path]);

        return redirect()
            ->route('profile')
            ->with('status', 'Avatar updated.');
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return redirect()
            ->route('profile')
            ->with('status', 'Avatar removed.');
    }
}
