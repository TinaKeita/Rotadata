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

    // skolotājs drīkst atjaunot studentu, kurš jebkad ir bijis kādā no viņa grupām –
    // nevis tikai to, kuras dēļ konts tika deaktivizēts (students var būt bijis vairākās grupās)
    public function restore(User $admin, User $member): bool
    {
        return $member->trashed()
            && $admin->adminGroups()
                ->whereHas('members', fn ($query) => $query->withTrashed()->whereKey($member->id))
                ->exists();
    }

    // tas pats nosacījums attiecas uz neatgriezenisku iztīrīšanu
    public function forceDelete(User $admin, User $member): bool
    {
        return $this->restore($admin, $member);
    }
}
