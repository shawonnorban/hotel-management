<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * Accounts imported from the old CodeIgniter site carry unsalted MD5 passwords.
 *
 * Those are still accepted once; Laravel then re-hashes the password with bcrypt/argon on
 * the same successful login (see rehashPasswordIfRequired in the parent class), so every
 * account upgrades itself the first time its owner signs in.
 */
class UpgradingUserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $plain = (string) ($credentials['password'] ?? '');
        $stored = (string) $user->getAuthPassword();

        if ($plain === '' || $stored === '') {
            return false;
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
            return hash_equals(strtolower($stored), md5($plain));
        }

        return Hash::check($plain, $stored);
    }
}
