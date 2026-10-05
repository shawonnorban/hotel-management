<?php

namespace App\Admin;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/** Renders a table cell for a field. */
class Formatter
{
    public static function cell(Field $field, Model $model): HtmlString|string
    {
        $value = $model->getAttribute($field->name);

        if ($value === null || $value === '') {
            return new HtmlString('<span class="text-body-tertiary">—</span>');
        }

        return match ($field->type) {
            'toggle' => new HtmlString($value ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-secondary">No</span>'),
            'money' => Money::format($value),
            'decimal' => number_format((float) $value, 2),
            'select' => e($field->resolveOptions()[$value] ?? $value),
            'image' => new HtmlString('<img src="'.e(asset($value)).'" alt="" class="rounded" style="height:36px;width:36px;object-fit:cover">'),
            'date' => e(\Illuminate\Support\Carbon::parse($value)->format('d M Y')),
            'datetime' => e(\Illuminate\Support\Carbon::parse($value)->format('d M Y H:i')),
            'password' => '••••••',
            default => e(\Illuminate\Support\Str::limit((string) $value, 60)),
        };
    }

    /** Plain text value for CSV export. */
    public static function plain(Field $field, Model $model): string
    {
        $value = $model->getAttribute($field->name);

        return match ($field->type) {
            'toggle' => $value ? 'Yes' : 'No',
            'select' => (string) ($field->resolveOptions()[$value] ?? $value),
            'password', 'image' => '',
            default => (string) $value,
        };
    }
}
