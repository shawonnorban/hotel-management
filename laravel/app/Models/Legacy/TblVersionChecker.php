<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblVersionChecker extends Model
{
    protected $table = 'tbl_version_checker';

    protected $primaryKey = 'vid';

    public $timestamps = false;

    protected $guarded = [];
}
