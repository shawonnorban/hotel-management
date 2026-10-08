<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HkLaundryProduct extends Model
{
    protected $table = 'hk_laundry_products';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function costs()
    {
        return $this->hasMany(HkLaundryCost::class, 'product_id');
    }
}
