<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $groupName;

    public function __construct(User $user, string $groupName)
    {
        $this->user = $user;
        $this->groupName = $groupName;
    }

    public function build()
    {
        return $this->subject("You've been added to “{$this->groupName}”")
            ->view('emails.member-added');
    }
}
