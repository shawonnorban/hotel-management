<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Bookingtype;

class BookingTypeResource extends Resource
{
    public static string $model = Bookingtype::class;

    public static string $slug = 'booking-types';

    public static string $label = 'Booking types';

    public static string $singular = 'Booking type';

    public static string $icon = 'bi-bookmarks';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('booktypetitle', 'Booking type')->required()->rules('max:255')->unique()->listed(),
        ];
    }
}
