<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'show_in_menu' => 'boolean', 'show_in_footer' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    /** Markdown body; raw HTML is stripped so editors cannot inject scripts. */
    public function getHtmlAttribute(): HtmlString
    {
        return new HtmlString(Str::markdown((string) $this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]));
    }
}
