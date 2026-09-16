<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;

class PurgeDeletedMembers extends Command
{
    protected $signature = 'members:purge';

    // aptver pašu dzēstus kontus un skolotāja atsevišķi izņemtus dalībniekus (grupa palikusi aktīva) –
    // grupas dzēšanas gadījumā deaktivizētos dalībniekus iztīra groups:purge kopā ar pašu grupu
    protected $description = 'Permanently deletes self-deleted accounts and individually removed members that have been soft-deleted for longer than the recovery window';

    public function handle(): int
    {
        $cutoff = now()->subDays(Group::PURGE_AFTER_DAYS);

        $members = User::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->where(function ($query) {
                $query->whereNull('deactivated_with_group_id') // pats dzēsis savu kontu
                    ->orWhereHas('deactivatedFromGroup'); // grupa vēl pastāv un nav dzēsta -> izņemts atsevišķi
            })
            ->get();

        if ($members->isEmpty()) {
            $this->info('No members to purge.');

            return self::SUCCESS;
        }

        foreach ($members as $member) {
            $member->forceDelete();
            $this->info("Purged member #{$member->id} — {$member->name}.");
        }

        return self::SUCCESS;
    }
}
