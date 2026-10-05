<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Roomdetails;
use App\Models\RoomfailityRefAccomodation;
use App\Models\Roomfacilitydetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** Which facilities each room type offers. */
class RoomFacilityResource extends Resource
{
    public static string $model = RoomfailityRefAccomodation::class;

    public static string $slug = 'room-facilities';

    public static string $label = 'Room facilities';

    public static string $singular = 'Room facility';

    public static string $icon = 'bi-check2-square';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::select('room_id', 'Room type', fn () => Roomdetails::orderBy('roomtype')->pluck('roomtype', 'roomid')->all())->required()->listed(),
            Field::select('facilityid', 'Facility', fn () => Roomfacilitydetails::orderBy('facilitytitle')->pluck('facilitytitle', 'facilityid')->all())->required()->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $facility = Roomfacilitydetails::find($data['facilityid']);
        $data['facilititypeid'] = $facility?->facilitytypeid ?? 0;

        $exists = RoomfailityRefAccomodation::where('room_id', $data['room_id'])->where('facilityid', $data['facilityid'])
            ->when($model, fn ($q) => $q->where('accomodationid', '!=', $model->getKey()))->exists();
        if ($exists) {
            throw ValidationException::withMessages(['facilityid' => 'This room type already has that facility.']);
        }

        return $data;
    }
}
