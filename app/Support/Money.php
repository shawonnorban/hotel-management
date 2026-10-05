<?php

namespace App\Support;

use App\Models\Currency;

/** Formats amounts with the hotel's configured currency symbol and position. */
class Money
{
    private static ?Currency $currency = null;

    private static bool $loaded = false;

    public static function currency(): ?Currency
    {
        if (! self::$loaded) {
            self::$loaded = true;
            $id = Settings::get('currency');
            self::$currency = $id ? Currency::find($id) : null;
        }

        return self::$currency;
    }

    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        $number = number_format((float) $amount, $decimals);
        $currency = self::currency();

        if (! $currency) {
            return $number;
        }

        return (int) $currency->position === 1 ? $currency->curr_icon.$number : $number.$currency->curr_icon;
    }

    public static function flush(): void
    {
        self::$currency = null;
        self::$loaded = false;
    }
}
