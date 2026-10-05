<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\ContactMessage;

class ContactMessageResource extends Resource
{
    public static string $model = ContactMessage::class;

    public static string $slug = 'messages';

    public static string $label = 'Contact messages';

    public static string $singular = 'Message';

    public static string $icon = 'bi-chat-left-text';

    public static string $group = 'Website';

    public function searchable(): array
    {
        return ['name', 'email', 'subject', 'message'];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Name')->required()->listed(),
            Field::email('email', 'Email')->required()->listed(),
            Field::text('phone', 'Phone'),
            Field::text('subject', 'Subject')->col(12)->listed(),
            Field::textarea('message', 'Message')->required()->attr('rows', 8),
            Field::datetime('created_at', 'Received')->listOnly(),
        ];
    }
}
