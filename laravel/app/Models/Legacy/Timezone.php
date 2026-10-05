<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    protected $table = 'timezone';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
