<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    // notikušus koncertus vairs nevar mainīt – tie paliek vēsturei un sezonas atskaitei
    public function update(User $user, Event $event): Response
    {
        if (! $user->ownsGroup($event->group)) {
            return Response::deny();
        }

        return $event->isPast()
            ? Response::deny("“{$event->title}” has already taken place, so it can't be edited.")
            : Response::allow();
    }

    public function delete(User $user, Event $event): Response
    {
        if (! $user->ownsGroup($event->group)) {
            return Response::deny();
        }

        return $event->isPast()
            ? Response::deny("“{$event->title}” has already taken place, so it can't be deleted.")
            : Response::allow();
    }
}
