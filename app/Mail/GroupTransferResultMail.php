<?php

namespace App\Mail;

use App\Models\GroupTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// nosūtītājam: saņēmējs pieņēma vai noraidīja grupas nodošanu
class GroupTransferResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GroupTransfer $transfer, public bool $accepted)
    {
    }

    public function build()
    {
        $subject = $this->accepted
            ? "{$this->transfer->toUser->name} took over “{$this->transfer->group->name}”"
            : "{$this->transfer->toUser->name} declined “{$this->transfer->group->name}”";

        return $this->subject($subject)
            ->view('emails.group-transfer-result')
            ->text('emails.text.group-transfer-result');
    }
}
