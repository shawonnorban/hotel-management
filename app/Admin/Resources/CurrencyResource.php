<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Currency;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;

class CurrencyResource extends Resource
{
    public static string $model = Currency::class;

    public static string $slug = 'currencies';

    public static string $label = 'Currencies';

    public static string $singular = 'Currency';

    public static string $icon = 'bi-currency-exchange';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('currencyname', 'Code (e.g. USD)')->required()->rules('max:50')->unique()->listed(),
            Field::text('curr_icon', 'Symbol')->required()->rules('max:10')->listed(),
            Field::select('position', 'Symbol position', [1 => 'Before the amount', 2 => 'After the amount'])->required()->listed(),
            Field::decimal('curr_rate', 'Rate vs. base currency')->required()->rules('min:0')->default(1)->listed(),
        ];
    }

    public function afterSave(Model $model, array $data, bool $created): void
    {
        Settings::flush();
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return (int) Settings::get('currency') === (int) $model->currencyid ? 'This is the hotel\'s active currency.' : null;
    }
}
