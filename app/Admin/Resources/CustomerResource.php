<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Customerinfo;
use Illuminate\Database\Eloquent\Model;

class CustomerResource extends Resource
{
    public const TITLES = ['Mr' => 'Mr', 'Mrs' => 'Mrs', 'Ms' => 'Ms', 'Miss' => 'Miss', 'Dr' => 'Dr', 'Prof' => 'Prof'];

    public const ID_TYPES = ['NID' => 'National ID', 'Passport' => 'Passport', 'Driving licence' => 'Driving licence', 'Birth certificate' => 'Birth certificate', 'Other' => 'Other'];

    public static string $model = Customerinfo::class;

    public static string $slug = 'customers';

    public static string $label = 'Customer list';

    public static string $singular = 'Customer';

    public static string $icon = 'bi-people';

    public static string $group = 'Customer';

    public static ?string $orderBy = 'customerid';

    public function searchable(): array
    {
        return ['firstname', 'lastname', 'email', 'cust_phone', 'customernumber', 'pid', 'passport'];
    }

    public function fields(): array
    {
        return [
            Field::heading('Guest details'),
            Field::select('title', 'Title', self::TITLES)->col(3),
            Field::text('country_code', 'Country code')->rules('max:10')->placeholder('+880')->col(3),
            Field::text('cust_phone', 'Mobile no.')->required()->rules('max:30')->unique()->col(6)->listed(),
            Field::text('firstname', 'First name')->required()->rules('max:100')->listed(),
            Field::text('lastname', 'Last name')->rules('max:100')->listed(),
            Field::text('fathername', 'Father name')->rules('max:150'),
            Field::text('profession', 'Occupation')->rules('max:100'),
            Field::select('gender', 'Gender', ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'])->col(4),
            Field::date('dob', 'Date of birth')->col(4),
            Field::date('anniversary', 'Anniversary')->col(4),
            Field::text('nationality', 'Nationality')->rules('max:100')->col(8),
            Field::toggle('is_vip', 'VIP guest')->default(0)->listed(),

            Field::heading('Contact details'),
            Field::email('email', 'Email')->rules('max:255')->unique()->listed(),
            Field::select('contacttype', 'Contact type', ['Home' => 'Home', 'Office' => 'Office', 'Mobile' => 'Mobile', 'Other' => 'Other']),
            Field::text('country', 'Country')->rules('max:100'),
            Field::text('state', 'State / division')->rules('max:100'),
            Field::text('city', 'City')->rules('max:100'),
            Field::text('zipcode', 'Zip code')->rules('max:100'),
            Field::text('address', 'Address')->rules('max:255')->col(12),

            Field::heading('Identity'),
            Field::select('pitype', 'Identity type', self::ID_TYPES),
            Field::text('pid', 'ID number')->rules('max:100'),
            Field::text('passport', 'Passport no.')->rules('max:100'),
            Field::text('visano', 'Visa no.')->rules('max:80'),
            Field::image('imgfront', 'ID – front side')->col(6),
            Field::image('imgback', 'ID – back side')->col(6),

            Field::heading('Guest photo & notes'),
            Field::image('imgguest', 'Guest photo')->col(6),
            Field::textarea('comments', 'Comments')->col(6),

            Field::heading('Website account'),
            Field::password('pass', 'Password')->help('Optional – lets the guest sign in on the website.'),
            Field::toggle('active', 'Account active')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['email'] = isset($data['email']) && $data['email'] !== '' ? strtolower((string) $data['email']) : null;
        $data['lastname'] = $data['lastname'] ?? '';
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
