<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Bedstype;

class BedTypeResource extends Resource
{
    public static string $model = Bedstype::class;

    public static string $slug = 'bed-types';

    public static string $label = 'Bed types';

    public static string $singular = 'Bed type';

    public static string $icon = 'bi-segmented-nav';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('bedstypetitle', 'Bed type')->required()->rules('max:255')->unique()->listed(),
        ];
    }
}
