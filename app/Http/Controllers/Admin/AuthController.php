<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        // Only active staff accounts (legacy usertype 1, status 1) may sign in.
        if (! Auth::guard('admin')->attempt($data + ['usertype' => 1, 'status' => 1])) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();
        Auth::guard('admin')->user()->forceFill(['last_login' => now(), 'ip_address' => substr((string) $request->ip(), 0, 14)])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->user()?->forceFill(['last_logout' => now()])->save();
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
