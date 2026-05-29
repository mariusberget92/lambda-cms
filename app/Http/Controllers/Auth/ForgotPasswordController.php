<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return Inertia::render("Auth/ForgotPassword");
    }

    public function send(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink($request->only("email"));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with("status", __($status));
        }

        return back()->withErrors(["email" => __($status)]);
    }
}
