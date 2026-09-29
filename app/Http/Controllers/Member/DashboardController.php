<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\CostumeSet;
use App\Models\Event;

// studenta sākumlapa: nākamais koncerts ar paša gatavību, koncertu saraksts un grupas ar to, kas šobrīd rokās
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        $groups = $user->memberGroups()->with('admin')->get();
        $groupIds = $groups->pluck('id');

        // koncerti no visām grupām, kurās students ir dalībnieks, sakārtoti pēc datuma
        $upcoming = Event::with(['costumes', 'group'])->whereIn('group_id', $groupIds)->upcoming()->get();
        $past = Event::with(['costumes', 'group'])->whereIn('group_id', $groupIds)->past()->get();

        // tuvākais koncerts, kurā students piedalās un kuram ir norādīti tērpi – tā pati gatavība, ko redz skolotājs
        $nextConcert = null;
        foreach ($upcoming as $event) {
            if ($event->costumes->isEmpty()) {
                continue;
            }

            $row = $event->readinessFor($user);
            if ($row) {
                $nextConcert = ['event' => $event, 'row' => $row];
                break;
            }
        }

        // šobrīd izsniegtās vienības, sagrupētas pa grupām (grupu kartītēm)
        $itemsByGroup = $user->assignedCostumeItems()
            ->with('costume')
            ->get()
            ->groupBy(fn ($item) => $item->costume->group_id);

        // komplektu nosaukumi grupu kartītēm
        $setNames = CostumeSet::whereIn('id', $groups->pluck('pivot.costume_set_id')->filter())->pluck('name', 'id');

        return view('dashboard', compact('groups', 'upcoming', 'past', 'nextConcert', 'itemsByGroup', 'setNames'));
    }
}
