<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblPostedbills extends Model
{
    protected $table = 'tbl_postedbills';

    protected $primaryKey = 'bill_id';

    public $timestamps = false;

    protected $guarded = [];
}
