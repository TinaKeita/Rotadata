<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// paziņo studentam, ka skolotājs viņu izņēmis no grupas (pretstats GroupInvitationMail)
class MemberRemovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $groupName;

    // datums, līdz kuram konts vēl atjaunojams; null, ja konts paliek aktīvs (students ir arī citās grupās)
    public ?string $restoreUntil;

    public function __construct(User $user, string $groupName, ?string $restoreUntil = null)
    {
        $this->user = $user;
        $this->groupName = $groupName;
        $this->restoreUntil = $restoreUntil;
    }

    public function build()
    {
        return $this->subject("You've been removed from “{$this->groupName}”")
            ->view('emails.member-removed')
            ->text('emails.text.member-removed');
    }
}
