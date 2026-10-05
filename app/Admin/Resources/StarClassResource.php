<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Starclass;

class StarClassResource extends Resource
{
    public static string $model = Starclass::class;

    public static string $slug = 'star-classes';

    public static string $label = 'Star classes';

    public static string $singular = 'Star class';

    public static string $icon = 'bi-stars';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('starclassname', 'Star class')->required()->rules('max:50')->unique()->listed(),
        ];
    }
}
