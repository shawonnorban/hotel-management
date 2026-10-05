<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Customerinfo;
use Illuminate\Database\Eloquent\Model;

class CustomerResource extends Resource
{
    public static string $model = Customerinfo::class;

    public static string $slug = 'customers';

    public static string $label = 'Guests';

    public static string $singular = 'Guest';

    public static string $icon = 'bi-people';

    public static string $group = 'Guests & sales';

    public static ?string $orderBy = 'customerid';

    public function searchable(): array
    {
        return ['firstname', 'lastname', 'email', 'cust_phone', 'customernumber'];
    }

    public function fields(): array
    {
        return [
            Field::text('firstname', 'First name')->required()->rules('max:100')->listed(),
            Field::text('lastname', 'Last name')->required()->rules('max:100')->listed(),
            Field::email('email', 'Email')->required()->unique()->listed(),
            Field::text('cust_phone', 'Phone')->required()->rules('max:30')->unique()->listed(),
            Field::select('gender', 'Gender', ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other']),
            Field::date('dob', 'Date of birth'),
            Field::text('address', 'Address')->rules('max:255')->col(12),
            Field::text('city', 'City')->rules('max:100'),
            Field::text('country', 'Country')->rules('max:100'),
            Field::text('nationality', 'Nationality')->rules('max:100'),
            Field::text('passport', 'Passport no.')->rules('max:100'),
            Field::text('profession', 'Profession')->rules('max:100'),
            Field::password('pass', 'Password')->help('Optional – lets the guest sign in on the website.'),
            Field::toggle('active', 'Account active')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['email'] = strtolower((string) $data['email']);
        $data['balance'] = $model?->balance ?? 0;
        $data['signupdate'] = $model?->signupdate ?? now()->toDateString();

        return $data;
    }

    public function afterSave(Model $model, array $data, bool $created): void
    {
        if ($created) {
            $model->forceFill(['customernumber' => str_pad((string) $model->customerid, 4, '0', STR_PAD_LEFT)])->save();
        }
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return $model->bookings()->exists() ? 'This guest has bookings and cannot be deleted. Deactivate the account instead.' : null;
    }
}
