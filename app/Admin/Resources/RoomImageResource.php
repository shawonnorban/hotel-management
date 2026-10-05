<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\RoomImage;
use App\Models\Roomdetails;
use Illuminate\Database\Eloquent\Model;

class RoomImageResource extends Resource
{
    public static string $model = RoomImage::class;

    public static string $slug = 'room-images';

    public static string $label = 'Room images';

    public static string $singular = 'Room image';

    public static string $icon = 'bi-images';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::select('room_id', 'Room type', fn () => Roomdetails::orderBy('roomtype')->pluck('roomtype', 'roomid')->all())->required()->listed(),
            Field::image('room_imagename', 'Image')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        // A new record needs a file; an existing one keeps its current image when none is uploaded.
        if (! $model && empty($data['room_imagename'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['room_imagename' => 'Please choose an image.']);
        }

        return $data;
    }
}
