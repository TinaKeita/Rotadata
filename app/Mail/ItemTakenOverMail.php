<?php

namespace App\Mail;

use App\Models\CostumeItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// paziņo iepriekšējam turētājam, ka cits grupas dalībnieks pārņēmis viņa tērpa vienību
class ItemTakenOverMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public CostumeItem $item,
        public User $takenBy,
    ) {}

    public function build()
    {
        return $this->subject("{$this->takenBy->name} took over {$this->item->code}")
            ->view('emails.item-taken-over')
            ->text('emails.text.item-taken-over');
    }
}
