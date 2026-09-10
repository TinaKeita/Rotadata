<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    // grupas iestatījumus un dzēšanu drīkst tikai tās skolotājs
    public function manage(User $user, Group $group): bool
    {
        return $user->ownsGroup($group);
    }

    public function update(User $user, Group $group): bool
    {
        return $user->ownsGroup($group);
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->ownsGroup($group);
    }

    public function restore(User $user, Group $group): bool
    {
        return $user->ownsGroup($group);
    }

    public function forceDelete(User $user, Group $group): bool
    {
        return $user->ownsGroup($group);
    }
}
