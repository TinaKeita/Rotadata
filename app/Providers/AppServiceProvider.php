<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // sagatavo sānjoslas skaitītājus katrai lapai, kur ir izvēlne
        View::composer('layouts.navigation', function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            if ($user->hasRole('admin')) {
                $group = $user->adminGroups()->withCount('members')->first();

                $view->with([
                    'navGroup' => $group,
                    'navMembersCount' => $group?->members_count ?? 0,
                    'navCostumesCount' => $group ? $group->costumes()->count() : 0,
                ]);

                return;
            }

            $view->with([
                'navGroup' => null,
                'navGroups' => $user->memberGroups,
            ]);
        });
    }
}
