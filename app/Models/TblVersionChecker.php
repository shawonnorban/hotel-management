<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblVersionChecker extends Model
{
    protected $table = 'tbl_version_checker';

    protected $primaryKey = 'vid';

    public $timestamps = false;

    protected $guarded = [];
}
