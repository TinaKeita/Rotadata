<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Mail\Mailable;

// skolotāja "Forgot password" saite Rotadata e-pasta noformējumā (Laravel noklusējuma veidnes vietā)
class ResetPasswordLinkNotification extends ResetPassword
{
    public function toMail($notifiable): Mailable
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new Mailable)
            ->to($notifiable->getEmailForPasswordReset())
            ->subject('Reset your Rotadata password')
            ->view('emails.password-reset-link')
            ->text('emails.text.password-reset-link')
            ->with([
                'user' => $notifiable,
                'url' => $url,
                'minutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]);
    }
}
