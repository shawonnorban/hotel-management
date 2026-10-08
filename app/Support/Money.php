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
        $currency = self::currency();
        $number = self::number((float) $amount, $decimals, $currency);

        if (! $currency) {
            return $number;
        }

        return (int) $currency->position === 1 ? $currency->curr_icon.$number : $number.$currency->curr_icon;
    }

    /**
     * Same as {@see format()} but safe for PDF fonts, which have no glyph for symbols such as ৳:
     * a non-ASCII symbol is replaced by the currency code ("BDT 1,25,000.00").
     */
    public static function pdf(float|int|string|null $amount, int $decimals = 2): string
    {
        $currency = self::currency();
        if (! $currency || mb_check_encoding($currency->curr_icon, 'ASCII')) {
            return self::format($amount, $decimals);
        }

        return $currency->currencyname.' '.self::number((float) $amount, $decimals, $currency);
    }

    /** Taka (and rupee) amounts are grouped the South Asian way: 12,34,567.00. */
    private static function number(float $amount, int $decimals, ?Currency $currency): string
    {
        if (! self::indianGrouping($currency)) {
            return number_format($amount, $decimals);
        }

        $negative = $amount < 0;
        [$int, $dec] = array_pad(explode('.', number_format(abs($amount), $decimals, '.', '')), 2, '');
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        $int = $rest !== '' ? preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).','.$last3 : $last3;

        return ($negative ? '-' : '').$int.($dec !== '' ? '.'.$dec : '');
    }

    /** Settings the browser needs to format amounts the same way. */
    public static function jsConfig(): array
    {
        $c = self::currency();

        return $c ? ['symbol' => $c->curr_icon, 'position' => (int) $c->position, 'indian' => self::indianGrouping($c)] : ['symbol' => '', 'position' => 1, 'indian' => false];
    }

    private static function indianGrouping(?Currency $currency): bool
    {
        return $currency && in_array(strtoupper((string) $currency->currencyname), ['BDT', 'INR', 'NPR', 'PKR', 'LKR'], true);
    }

    public static function flush(): void
    {
        self::$currency = null;
        self::$loaded = false;
    }
}
