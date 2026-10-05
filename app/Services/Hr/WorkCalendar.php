<?php

namespace App\Services\Hr;

use App\Models\HrHoliday;
use App\Support\AppSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Weekly days off plus public holidays. */
class WorkCalendar
{
    public const DAYS = ['sun' => 'Sunday', 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday'];

    private ?array $holidays = null;

    /** @return list<string> e.g. ['fri'] */
    public function weeklyOff(): array
    {
        $saved = AppSettings::get('hr.weekly_off', 'fri');

        return array_values(array_filter(explode(',', (string) $saved), fn ($d) => isset(self::DAYS[$d])));
    }

    public function isHoliday(CarbonInterface $date): bool
    {
        $this->holidays ??= HrHoliday::pluck('holiday_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->flip()->all();

        return isset($this->holidays[$date->toDateString()]);
    }

    public function isWeeklyOff(CarbonInterface $date): bool
    {
        return in_array(strtolower($date->format('D')), $this->weeklyOff(), true);
    }

    public function isWorkingDay(CarbonInterface $date): bool
    {
        return ! $this->isWeeklyOff($date) && ! $this->isHoliday($date);
    }

    /** Working days from..to inclusive. */
    public function workingDays(CarbonInterface $from, CarbonInterface $to): float
    {
        $n = 0;
        for ($d = Carbon::parse($from)->startOfDay(); $d->lte($to); $d->addDay()) {
            $n += $this->isWorkingDay($d) ? 1 : 0;
        }

        return (float) $n;
    }
}
