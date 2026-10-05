<?php

namespace App\Http\Controllers;

use App\Models\Customerinfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        // Legacy guests created by the old site have `active` = NULL; only an explicit 0 is blocked.
        $activeOnly = fn ($query) => $query->where(fn ($q) => $q->whereNull('active')->orWhere('active', '!=', 0));

        if (! Auth::guard('customer')->attempt($data + [$activeOnly])) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customerinfo', 'email')],
            'phone' => ['required', 'string', 'max:30', Rule::unique('customerinfo', 'cust_phone')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ]);

        $guest = DB::transaction(function () use ($data) {
            $guest = Customerinfo::create([
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => strtolower($data['email']),
                'cust_phone' => $data['phone'],
                'pass' => Hash::make($data['password']),
                'balance' => 0,
                'active' => 1,
                'signupdate' => now()->toDateString(),
            ]);
            $guest->update(['customernumber' => str_pad((string) $guest->customerid, 4, '0', STR_PAD_LEFT)]);

            return $guest;
        });

        Auth::guard('customer')->login($guest);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Welcome! Your account has been created.');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
