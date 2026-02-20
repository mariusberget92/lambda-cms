<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return Inertia::render("Auth/ForgotPassword");
    }

    public function send(Request $request)
    {
        $request->validate([
            "email" => ["required", "email"],
        ]);

        $status = Password::sendResetLink($request->only("email"));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with("status", __($status));
        }

        return back()->withErrors(["email" => __($status)]);
    }
}
