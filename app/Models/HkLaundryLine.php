<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HkLaundryLine extends Model
{
    protected $table = 'hk_laundry_lines';

    protected $guarded = [];
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(HkLaundryProduct::class, 'product_id');
    }
}
