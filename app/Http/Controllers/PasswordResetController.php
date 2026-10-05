<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/** Forgot / reset password for guests (broker "customers") and staff (broker "admins"). */
class PasswordResetController extends Controller
{
    public function request(Request $request)
    {
        return view('auth.forgot-password', ['broker' => $this->broker($request)]);
    }

    public function email(Request $request)
    {
        $broker = $this->broker($request);
        $request->validate(['email' => ['required', 'email']]);

        // Always answer the same way so the form cannot be used to discover which e-mails have accounts.
        Password::broker($broker)->sendResetLink($request->only('email'));

        return back()->with('status', 'If that e-mail address belongs to an account, a password reset link is on its way.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', ['broker' => $this->broker($request), 'token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $broker = $this->broker($request);
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::broker($broker)->reset($data, function ($user, $password) {
            $user->forceFill([$user->getAuthPasswordName() => Hash::make($password)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->route($broker === 'admins' ? 'admin.login' : 'login')->with('status', 'Your password has been reset. You can sign in now.');
    }

    private function broker(Request $request): string
    {
        return $request->route('broker') === 'admins' ? 'admins' : 'customers';
    }
}
