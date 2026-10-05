<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Bedstype extends Model
{
    protected $table = 'bedstype';

    protected $primaryKey = 'Bedstypeid';

    public $timestamps = false;

    protected $guarded = [];
}
