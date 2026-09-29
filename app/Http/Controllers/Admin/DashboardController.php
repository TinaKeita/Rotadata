<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Group;
use App\Support\GroupActivity;
use App\Support\Season;
use Illuminate\Support\Collection;

// skolotāja panelis kā darāmo darbu saraksts: kas jāizdara tagad, tuvākais koncerts un kluss kopsavilkums;
// detalizētā statistika pārcelta uz aktivitātes lapu (ActivityController)
class DashboardController extends Controller
{
    // koncerta rindas "Needs you" sarakstā parādās tikai tad, kad līdz koncertam ir ne vairāk kā tik dienu
    public const NEEDS_WINDOW_DAYS = 14;

    public function index()
    {
        $group = auth()->user()->adminGroups()->first();

        // piem. paziņojums, ka students pametis grupu – jāredz neatkarīgi no tā, vai grupa vēl pastāv
        $notifications = auth()->user()->unreadNotifications;

        if (! $group) {
            return view('admin.dashboard', [
                'group' => null,
                'notifications' => $notifications,
                'upcoming' => collect(),
                'past' => collect(),
            ]);
        }

        $upcoming = $group->events()->with(['costumes', 'group'])->upcoming()->get();
        $past = $group->events()->with(['costumes', 'group'])->past()->get();

        // tuvākais koncerts; kad tas ir pagājis, upcoming() automātiski dod nākamo
        $nextEvent = $upcoming->first();
        $readiness = $nextEvent?->studentReadiness();

        $activity = new GroupActivity($group);

        return view('admin.dashboard', [
            'group' => $group,
            'notifications' => $notifications,
            'upcoming' => $upcoming,
            'past' => $past,
            'nextEvent' => $nextEvent,
            'readiness' => $readiness,
            'needs' => $this->needs($group, $upcoming, $notifications),
            'fullyOut' => $activity->fullyOut(),
            'overview' => $activity->overview(),
            'today' => $activity->activityToday(),
        ]);
    }

    /**
     * "Needs you" rindas: tikai tas, ko skolotājs var izdarīt tagad.
     * Koncerta rindas parādās divas nedēļas pirms koncerta un pazūd pašas, kad problēma atrisināta vai koncerts pagājis;
     * paziņojumus un neizdevušos uzaicinājumu var aizvērt.
     */
    private function needs(Group $group, Collection $upcoming, Collection $notifications): array
    {
        $rows = [];

        // tikai koncerti tuvāko divu nedēļu laikā; tālākiem koncertiem vēl nav jāsatraucas
        $soon = $upcoming->filter(fn (Event $e) => $e->starts_at->lte(now()->addDays(self::NEEDS_WINDOW_DAYS)));

        foreach ($soon as $event) {
            $readiness = $event->studentReadiness();

            if (! $readiness['hasCostumes']) {
                continue;
            }

            // inventārā nepietiek vienību vajadzīgajam skaitam
            foreach ($event->costumeReadiness()->where('shortfall', '>', 0) as $row) {
                $rows[] = [
                    'type' => 'shortage',
                    'costume' => $row['costume'],
                    'total' => $row['total'],
                    'target' => $row['target'],
                    'shortfall' => $row['shortfall'],
                    'event' => $event,
                ];
            }

            // komplekts nav izvēlēts, bet koncertam ir komplektu tērpi
            $noSet = $readiness['students']->where('no_set', true);
            if ($noSet->isNotEmpty()) {
                $rows[] = [
                    'type' => 'no_set',
                    'students' => $noSet->pluck('student'),
                    'event' => $event,
                ];
            }

            // studentiem trūkst kāds sava komplekta tērps – katram sava rinda, saraksts ritinās
            foreach ($readiness['students']->filter(fn ($r) => ! $r['no_set'] && $r['missing']->isNotEmpty()) as $r) {
                $rows[] = [
                    'type' => 'missing',
                    'student' => $r['student'],
                    'missing' => $r['missing'],
                    'missingText' => $r['missingText'],
                    'event' => $event,
                ];
            }
        }

        // uzaicinājuma e-pasts nav piegādāts
        foreach ($group->members()->whereNotNull('invite_email_failed_at')->orderBy('name')->get() as $member) {
            $rows[] = ['type' => 'invite', 'student' => $member];
        }

        // students pametis grupu
        foreach ($notifications as $notification) {
            $rows[] = ['type' => 'left', 'notification' => $notification];
        }

        // sezonas noslēgums – laiks eksportēt atskaiti (no maija līdz augusta beigām)
        if (Season::isClosingSoon()) {
            $rows[] = ['type' => 'season', 'label' => Season::label()];
        }

        return $rows;
    }
}
