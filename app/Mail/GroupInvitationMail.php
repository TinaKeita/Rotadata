<?php

namespace App\Mail;

use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// uzaicina esošu kontu pievienoties grupai – lietotājs pieslēdzas un pats pieņem vai noraida (pretstats MemberRemovedMail)
class GroupInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public GroupInvitation $invitation,
    ) {}

    public function build()
    {
        return $this->subject("You're invited to join “{$this->invitation->group->name}”")
            ->view('emails.group-invitation')
            ->text('emails.text.group-invitation');
    }
}
