<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblCategory extends Model
{
    protected $table = 'tbl_category';

    protected $primaryKey = 'category_id';

    public $timestamps = false;

    protected $guarded = [];
}
