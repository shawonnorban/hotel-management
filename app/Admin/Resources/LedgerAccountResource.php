<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Model;

class LedgerAccountResource extends Resource
{
    public static string $model = LedgerAccount::class;

    public static string $slug = 'accounts';

    public static string $label = 'Chart of accounts';

    public static string $singular = 'Account';

    public static string $icon = 'bi-diagram-3';

    public static string $group = 'Accounting';

    public static ?string $orderBy = 'code';

    public static string $orderDirection = 'asc';

    public function with(): array
    {
        return ['parent'];
    }

    public function fields(): array
    {
        return [
            Field::text('code', 'Code')->required()->rules('max:20|alpha_dash')->unique()->listed(),
            Field::text('name', 'Name')->required()->rules('max:150')->listed(),
            Field::select('type', 'Type', LedgerAccount::TYPES)->required()->listed(),
            Field::select('parent_id', 'Parent group', fn () => LedgerAccount::where('is_group', true)->orderBy('code')->get()->mapWithKeys(fn ($a) => [$a->id => $a->label])->all()),
            Field::money('opening_balance', 'Opening balance')->rules('numeric')->default(0)->help('Balance in the account\'s normal direction when you start using the system.'),
            Field::toggle('is_group', 'Group (heading only, cannot be posted to)')->default(0),
            Field::toggle('is_cash', 'Cash / bank account (appears in the cash book and payment screens)')->default(0),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        if ($model?->system_key) {
            // System accounts keep their type and group flag; the ledger relies on them.
            $data['type'] = $model->type;
            $data['is_group'] = $model->is_group;
        }

        return $data;
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return match (true) {
            (bool) $model->system_key => 'This is a system account used by automatic postings and cannot be deleted.',
            $model->children()->exists() => 'This account has sub-accounts.',
            $model->lines()->exists() => 'This account has transactions. Mark it inactive instead.',
            default => null,
        };
    }
}
