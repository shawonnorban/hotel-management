<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * User provider for the legacy CodeIgniter tables.
 *
 * The CodeIgniter app stores passwords as unsalted MD5 hashes. While both apps share
 * one database, those hashes are still accepted here (compared in constant time).
 * New-style bcrypt hashes are accepted too, so passwords can be upgraded later.
 */
class LegacyUserProvider extends EloquentUserProvider
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

    /**
     * Never silently rewrite the stored hash: the legacy `user.password` column is only 32 characters wide
     * and the CodeIgniter site still verifies MD5 against the same rows.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void {}
}
