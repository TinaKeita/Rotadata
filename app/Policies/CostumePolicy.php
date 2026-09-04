<?php

namespace App\Policies;

use App\Models\Costume;
use App\Models\User;

class CostumePolicy
{
    // skolotājs drīkst skatīt tikai savas grupas tērpus
    public function view(User $user, Costume $costume): bool
    {
        return $user->ownsGroup($costume->group);
    }

    public function update(User $user, Costume $costume): bool
    {
        return $user->ownsGroup($costume->group);
    }

    public function delete(User $user, Costume $costume): bool
    {
        return $user->ownsGroup($costume->group);
    }
}
