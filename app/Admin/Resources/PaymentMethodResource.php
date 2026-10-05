<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\LedgerAccount;
use App\Models\PaymentMethod;

class PaymentMethodResource extends Resource
{
    public static string $model = PaymentMethod::class;

    public static string $slug = 'payment-methods';

    public static string $label = 'Payment methods';

    public static string $singular = 'Payment method';

    public static string $icon = 'bi-credit-card-2-front';

    public static string $group = 'Administration';

    public static string $orderDirection = 'asc';

    public function fields(): array
    {
        return [
            Field::text('payment_method', 'Name')->required()->rules('max:100')->unique()->listed(),
            Field::select('ledger_account_id', 'Money is received into', fn () => LedgerAccount::where('is_cash', true)->where('is_group', false)->orderBy('code')->get()->mapWithKeys(fn ($a) => [$a->id => $a->label])->all())->required()->help('The cash, bank or gateway account that payments by this method are posted to.'),
            Field::toggle('is_active', 'Offered to guests')->listed(),
        ];
    }
}
