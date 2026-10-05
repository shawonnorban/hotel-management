<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    public static string $model = Page::class;

    public static string $slug = 'pages';

    public static string $label = 'Website pages';

    public static string $singular = 'Page';

    public static string $icon = 'bi-file-earmark-text';

    public static string $group = 'Website';

    public static ?string $orderBy = 'sort';

    public static string $orderDirection = 'asc';

    public function fields(): array
    {
        return [
            Field::text('title', 'Title')->required()->rules('max:150')->listed(),
            Field::text('slug', 'Address (URL)')->rules('max:120|alpha_dash')->unique()->help('Leave blank to create it from the title.')->listed(),
            Field::textarea('body', 'Content')->required()->attr('rows', 12)->help('Markdown is supported (## headings, **bold**, lists, links).'),
            Field::number('sort', 'Order')->rules('min:0|max:999')->default(0)->listed(),
            Field::toggle('published', 'Published')->listed(),
            Field::toggle('show_in_menu', 'Show in the top menu')->default(0),
            Field::toggle('show_in_footer', 'Show in the footer')->default(0),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['slug'] = Str::slug($data['slug'] ?: $data['title']);
        $data['sort'] = (int) ($data['sort'] ?? 0);

        // A generated slug can collide with another page.
        $base = $data['slug'];
        $i = 2;
        while (Page::where('slug', $data['slug'])->when($model, fn ($q) => $q->whereKeyNot($model->getKey()))->exists()) {
            $data['slug'] = $base.'-'.$i++;
        }

        return $data;
    }
}
