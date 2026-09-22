<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    // studenti drīkst skatīt sava grupas koncertu, skolotājs — tikai savas grupas
    public function view(User $user, Event $event): bool
    {
        return $user->ownsGroup($event->group) || $user->inGroup($event->group);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->ownsGroup($event->group);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->ownsGroup($event->group);
    }
}
