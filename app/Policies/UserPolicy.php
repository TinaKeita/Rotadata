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

    // skolotājs drīkst izņemt savas grupas dalībnieku – ja tas ir skolotājs vai ir arī citā grupā,
    // kontrolieris tikai atsaista no grupas, nevis dzēš kontu, tāpēc šeit sevi izslēdzam, bet ne citus skolotājus
    public function delete(User $admin, User $member): bool
    {
        return $admin->id !== $member->id
            && $admin->sharesGroupWithMember($member);
    }

    // skolotājs drīkst atjaunot tikai savas grupas dēļ deaktivizētu studentu
    public function restore(User $admin, User $member): bool
    {
        return $member->deactivated_with_group_id !== null
            && $admin->adminGroups()->whereKey($member->deactivated_with_group_id)->exists();
    }

    // tas pats nosacījums attiecas uz neatgriezenisku iztīrīšanu
    public function forceDelete(User $admin, User $member): bool
    {
        return $this->restore($admin, $member);
    }
}
