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
        // jaunai parolei: vismaz 8 simboli, lielais un mazais burts, cipars un speciālā zīme
        \Illuminate\Validation\Rules\Password::defaults(
            fn () => \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()
        );

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
                $current = $user->currentGroup();
                $group = $current ? $current->loadCount('members') : null;

                $view->with([
                    'navGroup' => $group,
                    // visas skolotāja grupas – navigācijā rāda to nosaukumus (klikšķis atver grupas iestatījumus)
                    'navOwnedGroups' => $user->adminGroups()->orderBy('id')->get(['id', 'name']),
                    'navMembersCount' => $group?->members_count ?? 0,
                    'navCostumesCount' => $group ? $group->costumes()->count() : 0,
                    'navEventsCount' => $group ? $group->events()->upcoming()->count() : 0,
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
