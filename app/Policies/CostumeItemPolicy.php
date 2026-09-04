<?php

namespace App\Policies;

use App\Models\CostumeItem;
use App\Models\User;

class CostumeItemPolicy
{
    // skolotājs noņem vienību no studenta – tikai savas grupas tērpiem
    public function unassignAsAdmin(User $user, CostumeItem $item): bool
    {
        return $user->ownsGroup($item->costume->group);
    }

    // students atdod savu vienību – tikai to, kas piešķirta viņam pašam
    public function unassignAsMember(User $user, CostumeItem $item): bool
    {
        return $item->assigned_to === $user->id;
    }

    // students paņem noskenēto vienību – tikai ja tā ir brīva un viņš ir grupā
    public function claim(User $user, CostumeItem $item): bool
    {
        return is_null($item->assigned_to)
            && $user->inGroup($item->costume->group);
    }
}
