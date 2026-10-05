<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class NoticeBoard extends Model
{
    protected $table = 'notice_board';

    protected $primaryKey = 'notice_id';

    public $timestamps = false;

    protected $guarded = [];
}
