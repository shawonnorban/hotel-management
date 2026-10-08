<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\TblOtherguest;

/** Companions registered against bookings (the "Guest list" screen). */
class GuestListResource extends Resource
{
    public static string $model = TblOtherguest::class;

    public static string $slug = 'guest-list';

    public static string $label = 'Guest list';

    public static string $singular = 'Guest';

    public static string $icon = 'bi-people';

    public static string $group = 'Customer';

    public function searchable(): array
    {
        return ['guestname', 'mobile', 'email', 'photo_id'];
    }

    public function fields(): array
    {
        return [
            Field::text('guestname', 'Name')->required()->rules('max:150')->listed(),
            Field::select('gender', 'Gender', ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'])->listed(),
            Field::text('mobile', 'Mobile')->rules('max:30')->listed(),
            Field::email('email', 'Email'),
            Field::select('photo_id_type', 'ID type', ['NID' => 'NID', 'Passport' => 'Passport', 'Driving licence' => 'Driving licence', 'Other' => 'Other']),
            Field::text('photo_id', 'ID number')->rules('max:100')->listed(),
            Field::image('front_image', 'ID photo — front'),
            Field::image('back_image', 'ID photo — back'),
            Field::image('occupant_image', 'Guest photo'),
        ];
    }
}
