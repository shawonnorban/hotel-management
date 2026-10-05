<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TopMenu extends Model
{
    protected $table = 'top_menu';

    protected $primaryKey = 'menuid';

    public $timestamps = false;

    protected $guarded = [];
}
