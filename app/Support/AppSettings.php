<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Small key/value store for settings that are not part of the hotel profile (mail server, …). */
class AppSettings
{
    /** Keys whose values are encrypted at rest. */
    private const SECRET = ['mail.password'];

    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            if (Schema::hasTable('app_settings')) {
                foreach (DB::table('app_settings')->get() as $row) {
                    self::$cache[$row->key] = in_array($row->key, self::SECRET, true) && $row->value ? self::decrypt($row->value) : $row->value;
                }
            }
        }

        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function set(string $key, ?string $value): void
    {
        $stored = in_array($key, self::SECRET, true) && $value ? Crypt::encryptString($value) : $value;
        DB::table('app_settings')->updateOrInsert(['key' => $key], ['value' => $stored, 'updated_at' => now(), 'created_at' => now()]);
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    private static function decrypt(string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null; // key rotated: treat as not set rather than crash the site
        }
    }

    /** Apply the saved SMTP settings to Laravel's mail configuration. */
    public static function applyMailConfig(): void
    {
        if (! self::get('mail.host')) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => self::get('mail.host'),
            'mail.mailers.smtp.port' => (int) self::get('mail.port', 587),
            'mail.mailers.smtp.username' => self::get('mail.username'),
            'mail.mailers.smtp.password' => self::get('mail.password'),
            'mail.mailers.smtp.scheme' => self::get('mail.encryption') === 'ssl' ? 'smtps' : 'smtp',
            'mail.from.address' => self::get('mail.from_address', config('mail.from.address')),
            'mail.from.name' => self::get('mail.from_name', Settings::hotelName()),
        ]);
    }
}
