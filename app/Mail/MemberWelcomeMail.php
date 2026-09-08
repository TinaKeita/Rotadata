<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $password;
    public ?string $groupName;

    public function __construct(User $user, string $password, ?string $groupName = null)
    {
        $this->user = $user;
        $this->password = $password;
        $this->groupName = $groupName;
    }

    public function build()
    {
        return $this->subject('Tavs Rotadata konts ir izveidots')
            ->view('emails.member-welcome');
    }
}
