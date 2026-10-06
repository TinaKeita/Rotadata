<?php

namespace App\Policies;

use App\Models\GroupTransfer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GroupTransferPolicy
{
    // pieprasījumu atvērt, pieņemt vai noraidīt drīkst tikai skolotājs, kuram tas adresēts
    public function respond(User $user, GroupTransfer $transfer): Response
    {
        return (int) $transfer->to_user_id === $user->id
            ? Response::allow()
            : Response::deny('This request was sent to another teacher.');
    }
}
