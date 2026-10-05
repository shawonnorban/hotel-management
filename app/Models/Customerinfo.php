<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Hotel guests (legacy `customerinfo` table). */
class Customerinfo extends Authenticatable
{
    use Notifiable;

    protected $table = 'customerinfo';

    protected $primaryKey = 'customerid';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['pass', 'password_reset_token'];

    public function getAuthPassword(): string
    {
        return (string) $this->pass;
    }

    public function getAuthPasswordName(): string
    {
        return 'pass';
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

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token, 'password.reset'));
    }
}
