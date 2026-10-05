<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblComplementary extends Model
{
    protected $table = 'tbl_complementary';

    protected $primaryKey = 'complementary_id';

    public $timestamps = false;

    protected $guarded = [];
}
