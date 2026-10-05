<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit()
    {
        return view('account.edit', ['guest' => Auth::guard('customer')->user()]);
    }

    public function update(Request $request)
    {
        $guest = Auth::guard('customer')->user();

        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customerinfo', 'email')->ignore($guest->customerid, 'customerid')],
            'cust_phone' => ['required', 'string', 'max:30', Rule::unique('customerinfo', 'cust_phone')->ignore($guest->customerid, 'customerid')],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'current_password' => ['nullable', 'required_with:password', fn ($attribute, $value, $fail) => Auth::guard('customer')->getProvider()->validateCredentials(Auth::guard('customer')->user(), ['password' => $value]) || $fail('The current password is incorrect.')],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $guest->fill(collect($data)->only(['firstname', 'lastname', 'email', 'cust_phone', 'address', 'city', 'country'])->all());
        if (! empty($data['password'])) {
            $guest->pass = Hash::make($data['password']);
        }
        $guest->save();

        return back()->with('status', 'Your details have been saved.');
    }
}
