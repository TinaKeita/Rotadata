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
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
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
                    // skolotājs var būt arī cita skolotāja grupas dalībnieks – rāda šīs grupas atsevišķi
                    'navMemberGroups' => $user->memberGroups,
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
