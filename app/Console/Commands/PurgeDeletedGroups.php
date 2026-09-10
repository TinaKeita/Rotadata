<?php

namespace App\Console\Commands;

use App\Models\Group;
use Illuminate\Console\Command;

class PurgeDeletedGroups extends Command
{
    protected $signature = 'groups:purge';

    protected $description = 'Permanently deletes groups that have been soft-deleted for longer than the recovery window';

    public function handle(): int
    {
        $cutoff = now()->subDays(Group::PURGE_AFTER_DAYS);

        $groups = Group::onlyTrashed()->where('deleted_at', '<=', $cutoff)->get();

        if ($groups->isEmpty()) {
            $this->info('No groups to purge.');

            return self::SUCCESS;
        }

        foreach ($groups as $group) {
            $group->purge();
            $this->info("Purged group #{$group->id} — {$group->name}.");
        }

        return self::SUCCESS;
    }
}
