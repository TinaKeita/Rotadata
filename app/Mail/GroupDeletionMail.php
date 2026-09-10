<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GroupDeletionMail extends Mailable
{
    use Queueable, SerializesModels;

    // pieļaujamie varianti
    public const DEACTIVATED = 'deactivated'; // dalībnieks bija tikai šajā grupā – konts deaktivizēts
    public const REMOVED = 'removed';         // dalībnieks ir citās grupās – tikai izņemts no šīs
    public const RESTORED = 'restored';       // grupa atjaunota – konts atkal aktīvs

    public User $user;
    public string $groupName;
    public string $variant;
    public ?string $purgeDate;

    public function __construct(User $user, string $groupName, string $variant, ?string $purgeDate = null)
    {
        $this->user = $user;
        $this->groupName = $groupName;
        $this->variant = $variant;
        $this->purgeDate = $purgeDate;
    }

    public function build()
    {
        $subject = $this->variant === self::RESTORED
            ? 'Your Rotadata account has been restored'
            : "Group “{$this->groupName}” was deleted";

        return $this->subject($subject)->view('emails.group-deletion');
    }
}
