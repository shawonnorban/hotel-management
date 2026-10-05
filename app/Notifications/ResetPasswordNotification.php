<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

/** Password-reset mail whose link points at the guest or staff reset screen. */
class ResetPasswordNotification extends ResetPassword
{
    public function __construct(string $token, private string $routeName)
    {
        parent::__construct($token);
    }

    protected function resetUrl($notifiable): string
    {
        return url(route($this->routeName, [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
