<?php

namespace App\Policies;

use App\Models\CostumeSet;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CostumeSetPolicy
{
    // komplektu drīkst pārdēvēt tikai tās grupas skolotājs; iebūvētos komplektus ("Girls", "Boys") nemaina
    public function update(User $user, CostumeSet $costumeSet): Response
    {
        if (! $user->ownsGroup($costumeSet->group)) {
            return Response::deny();
        }

        return $costumeSet->built_in
            ? Response::deny('Built-in sets cannot be renamed.')
            : Response::allow();
    }

    public function delete(User $user, CostumeSet $costumeSet): Response
    {
        if (! $user->ownsGroup($costumeSet->group)) {
            return Response::deny();
        }

        return $costumeSet->built_in
            ? Response::deny('Built-in sets cannot be deleted.')
            : Response::allow();
    }
}
