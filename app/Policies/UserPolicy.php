<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // skolotājs drīkst skatīt tikai savas grupas studentus
    public function view(User $admin, User $member): bool
    {
        return $admin->sharesGroupWithMember($member);
    }

    // skolotājs drīkst dzēst savas grupas studentu, bet ne sevi un ne citu skolotāju
    public function delete(User $admin, User $member): bool
    {
        return $admin->id !== $member->id
            && ! $member->hasRole('admin')
            && $admin->sharesGroupWithMember($member);
    }
}
