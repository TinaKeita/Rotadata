<?php

namespace App\Notifications;

use App\Models\CostumeItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// paziņo skolotājam, ka students noskenējis un pārņēmis cita studenta tērpa vienību – rāda kā rindu panelī,
// lai skolotājs to redz uzreiz un vajadzības gadījumā var vienību atdot atpakaļ
class ItemTakenOverNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CostumeItem $item,
        public User $from,
        public User $to,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'costume_id' => $this->item->costume_id,
            'costume_name' => $this->item->costume->name,
            'item_code' => $this->item->code,
            'from_name' => $this->from->name,
            'to_name' => $this->to->name,
        ];
    }
}
