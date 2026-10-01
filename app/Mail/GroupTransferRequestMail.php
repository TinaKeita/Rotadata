<?php

namespace App\Mail;

use App\Models\GroupTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// saņēmējam: cits skolotājs vēlas nodot viņam savu grupu – ar saiti pieņemšanai vai noraidīšanai
class GroupTransferRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GroupTransfer $transfer, public array $impact)
    {
    }

    public function build()
    {
        return $this->subject("{$this->transfer->fromUser->name} wants to hand you “{$this->transfer->group->name}”")
            ->view('emails.group-transfer-request')
            ->text('emails.text.group-transfer-request');
    }
}
