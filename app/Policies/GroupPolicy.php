<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    // students redz savus tērpus grupā un var to pamest tikai, ja ir šīs grupas dalībnieks
    public function viewAsMember(User $user, Group $group): bool
    {
        return $user->inGroup($group);
    }

    public function leave(User $user, Group $group): bool
    {
        return $user->inGroup($group);
    }

    // grupas iestatījumus un dzēšanu drīkst tikai tās skolotājs
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
