<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Customerinfo;
use App\Models\TblWakeupCall;

class WakeUpCallResource extends Resource
{
    public static string $model = TblWakeupCall::class;

    public static string $slug = 'wake-up-calls';

    public static string $label = 'Wake-up calls';

    public static string $singular = 'Wake-up call';

    public static string $icon = 'bi-alarm';

    public static string $group = 'Front desk';

    public function with(): array
    {
        return [];
    }

    public function fields(): array
    {
        return [
            Field::select('custid', 'Guest', fn () => Customerinfo::orderBy('firstname')->get()->mapWithKeys(fn ($c) => [$c->customerid => trim($c->firstname.' '.$c->lastname).' · '.$c->cust_phone])->all())->required()->listed(),
            Field::datetime('wakeupcall_time', 'Call time')->required()->listed(),
            Field::textarea('remarks', 'Remarks')->listed(),
        ];
    }
}
