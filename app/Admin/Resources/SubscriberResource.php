<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Subscriber;

class SubscriberResource extends Resource
{
    public static string $model = Subscriber::class;

    public static string $slug = 'subscribers';

    public static string $label = 'Newsletter subscribers';

    public static string $singular = 'Subscriber';

    public static string $icon = 'bi-envelope-heart';

    public static string $group = 'Website';

    public function fields(): array
    {
        return [
            Field::email('email', 'Email')->required()->unique()->listed(),
            Field::datetime('created_at', 'Subscribed')->listOnly(),
        ];
    }
}
