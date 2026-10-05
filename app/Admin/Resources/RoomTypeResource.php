<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Bedstype;
use App\Models\Roomdetails;
use App\Models\Roomsizemesurement;
use App\Models\Starclass;
use Illuminate\Database\Eloquent\Model;

class RoomTypeResource extends Resource
{
    public static string $model = Roomdetails::class;

    public static string $slug = 'room-types';

    public static string $label = 'Room types';

    public static string $singular = 'Room type';

    public static string $icon = 'bi-door-open';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('roomtype', 'Room type')->required()->rules('max:255')->listed(),
            Field::select('bedstype', 'Bed type', fn () => Bedstype::orderBy('bedstypetitle')->pluck('bedstypetitle', 'Bedstypeid')->all())->required()->listed(),
            Field::number('bedsno', 'Number of beds')->required()->rules('min:1|max:20')->default(1),
            Field::number('capacity', 'Guest capacity')->required()->rules('min:1|max:50')->default(2)->listed(),
            Field::number('child_limit', 'Child limit')->rules('min:0|max:50')->default(0),
            Field::number('exbedcapability', 'Extra beds allowed')->rules('min:0|max:10')->default(0),
            Field::number('roomsize', 'Room size')->required()->rules('min:1'),
            Field::select('roomsizemesurement', 'Size unit', fn () => Roomsizemesurement::orderBy('roommesurementitle')->pluck('roommesurementitle', 'mesurementid')->all())->required(),
            Field::select('number_of_star', 'Star class', fn () => Starclass::orderBy('starclassname')->pluck('starclassname', 'starcalssid')->all()),
            Field::money('rate', 'Rate per night')->required()->rules('min:0')->listed(),
            Field::money('bedcharge', 'Extra bed charge')->required()->rules('min:0')->default(0),
            Field::money('personcharge', 'Extra person charge')->required()->rules('min:0')->default(0),
            Field::textarea('roomdescription', 'Description')->required()->rules('max:255'),
            Field::textarea('reservecondition', 'Booking conditions'),
            Field::toggle('roomactive', 'Bookable online')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['roomstatus'] = $model?->roomstatus ?? 0;
        $data['roomactive'] = (int) $data['roomactive'];

        return $data;
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return $model->roomNumbers()->exists() ? 'Remove this room type\'s room numbers first.' : null;
    }
}
