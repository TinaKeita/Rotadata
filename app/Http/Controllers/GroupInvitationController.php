<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GroupInvitation;
use Illuminate\Support\Facades\DB;

// uzaicinātais lietotājs (students vai cits skolotājs) pats pieņem vai noraida uzaicinājumu pievienoties grupai
class GroupInvitationController extends Controller
{
    public function accept(GroupInvitation $invitation)
    {
        abort_unless((int) $invitation->user_id === auth()->id(), 403);

        // jauns dalībnieks pēc koncerta tajā nepiedalījās – notikušie koncerti tiek nofiksēti pirms pievienošanas
        Event::snapshotFinished();

        // pārbaudi atkārto ar bloķētu rindu – vienu uzaicinājumu nevar reizē pieņemt un atsaukt vai noraidīt
        $group = DB::transaction(function () use ($invitation) {
            $locked = GroupInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isOpen(), 410, 'This invitation is no longer open.');

            $user = auth()->user();
            $group = $locked->group;

            if (! $user->hasRole('member')) {
                $user->assignRole('member');
            }

            if (! $user->inGroup($group)) {
                $group->members()->attach($user->id, ['costume_set_id' => $locked->costume_set_id]);
            }

            $locked->update(['status' => 'accepted', 'responded_at' => now()]);

            return $group;
        });

        return back()->with('success', "You've joined “{$group->name}”.");
    }

    public function decline(GroupInvitation $invitation)
    {
        abort_unless((int) $invitation->user_id === auth()->id(), 403);

        $updated = GroupInvitation::whereKey($invitation->id)->where('status', 'pending')
            ->update(['status' => 'declined', 'responded_at' => now()]);

        abort_if($updated === 0, 410, 'This invitation is no longer open.');

        return back()->with('success', 'Invitation declined. You were not added to the group.');
    }
}
