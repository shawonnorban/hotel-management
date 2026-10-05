<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblNote extends Model
{
    protected $table = 'tbl_note';

    protected $primaryKey = 'note_id';

    public $timestamps = false;

    protected $guarded = [];
}
