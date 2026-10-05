<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/** Hotel staff / admin accounts (legacy `user` table). */
class User extends Authenticatable
{
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'admin';

    protected $table = 'user';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['password', 'password_reset_token', 'device_token'];

    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    // The legacy schema has no remember_token column.
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstname.' '.$this->lastname) ?: $this->email;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token, 'admin.password.reset'));
    }
}
