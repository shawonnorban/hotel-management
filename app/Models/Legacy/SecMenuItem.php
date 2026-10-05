<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class SecMenuItem extends Model
{
    protected $table = 'sec_menu_item';

    protected $primaryKey = 'menu_id';

    public $timestamps = false;

    protected $guarded = [];
}
