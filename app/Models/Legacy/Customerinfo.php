<?php

namespace App\Models\Legacy;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** Hotel guests (legacy `customerinfo` table). */
class Customerinfo extends Authenticatable
{
    protected $table = 'customerinfo';

    protected $primaryKey = 'customerid';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['pass', 'password_reset_token'];

    public function getAuthPassword(): string
    {
        return (string) $this->pass;
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstname.' '.$this->lastname);
    }

    public function bookings()
    {
        return $this->hasMany(BookedInfo::class, 'cutomerid', 'customerid');
    }
}
