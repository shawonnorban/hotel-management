<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PageTitle extends Model
{
    protected $table = 'page_title';

    protected $primaryKey = 'pageid';

    public $timestamps = false;

    protected $guarded = [];
}
