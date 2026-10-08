<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Hall;
use Illuminate\Database\Eloquent\Model;

class HallResource extends Resource
{
    public static string $model = Hall::class;

    public static string $slug = 'hall-rooms';

    public static string $label = 'Hallroom assign';

    public static string $singular = 'Hall';

    public static string $icon = 'bi-building';

    public static string $group = 'Hall room';

    public function with(): array
    {
        return ['type', 'facilities'];
    }

    public function searchable(): array
    {
        return ['name', 'location'];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Hall name')->required()->rules('max:120')->unique()->listed(),
            Field::select('type_id', 'Type', fn () => \App\Models\HallType::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->required()->listed(),
            Field::number('capacity', 'Capacity (people)')->required()->rules('integer|min:1|max:100000')->listed(),
            Field::money('rate_per_hour', 'Rate per hour')->default(0)->rules('min:0')->listed(),
            Field::money('rate_per_day', 'Full-day rate')->default(0)->rules('min:0')->help('Optional cap: a booking never costs more than this.'),
            Field::text('location', 'Floor / location')->rules('max:120'),
            Field::multiselect('facility_ids', 'Facilities', fn () => \App\Models\HallFacility::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->virtual(fn ($hall) => $hall->facilities->pluck('id')->all()),
            Field::textarea('description', 'Description')->col(12),
            Field::toggle('is_active', 'Available')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['rate_per_hour'] = $data['rate_per_hour'] ?? 0;
        $data['rate_per_day'] = $data['rate_per_day'] ?? 0;

        return $data;
    }

    public function afterSave(Model $model, array $data, bool $created): void
    {
        $model->facilities()->sync($data['facility_ids'] ?? []);
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return \App\Models\HallBooking::where('hall_id', $model->id)->exists() ? 'This hall has bookings. Mark it unavailable instead.' : null;
    }
}
