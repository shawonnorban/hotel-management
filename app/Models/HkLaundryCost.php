<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HkLaundryCost extends Model
{
    protected $table = 'hk_laundry_costs';

    protected $guarded = [];

    public const SERVICES = ['wash' => 'Wash', 'iron' => 'Iron', 'wash_iron' => 'Wash & iron', 'dry_clean' => 'Dry clean'];

    public function product()
    {
        return $this->belongsTo(HkLaundryProduct::class, 'product_id');
    }
}
