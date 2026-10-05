<?php

namespace App\Support;

use App\Models\Setting;

/** Hotel-wide settings: the single row (id 2) of the `setting` table. */
class Settings
{
    public const ROW_ID = 2;

    private static ?Setting $row = null;

    public static function row(): Setting
    {
        return self::$row ??= Setting::query()->find(self::ROW_ID)
            ?? Setting::query()->first()
            ?? new Setting(['id' => self::ROW_ID, 'title' => config('app.name')]);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::row()->getAttribute($key) ?? $default;
    }

    public static function hotelName(): string
    {
        return (string) (self::get('title') ?: config('app.name'));
    }

    /** Forget the cached row (after it was edited, and between tests). */
    public static function flush(): void
    {
        self::$row = null;
        Money::flush();
    }
}
