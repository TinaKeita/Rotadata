<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// paziņo skolotājam, ka students pametis (vai ticis izņemts no) grupu – rāda kā baneri panelī
class StudentLeftGroupNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $groupName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'student_name' => $this->studentName,
            'group_name' => $this->groupName,
        ];
    }
}
