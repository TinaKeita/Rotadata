<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\GroupActivity;

// tērpu aprites statistika un žurnāls – pārcelts no paneļa, lai panelī paliek tikai darāmais
class ActivityController extends Controller
{
    public function index()
    {
        $group = auth()->user()->adminGroups()->first();

        if (! $group) {
            return view('admin.activity', ['group' => null, 'stats' => null]);
        }

        $activity = new GroupActivity($group);

        $stats = [
            'overview'       => $activity->overview(),
            'activityWeek'   => $activity->activityThisWeek(),
            'readiness'      => $activity->readiness(),
            'feed'           => $activity->recentActivity(25),
            'longestOut'     => $activity->longestOut(),
            'topHolders'     => $activity->topHolders(),
            'fullyOut'       => $activity->fullyOut(),
            'inDemand'       => $activity->inDemand(),
            'mostTravelled'  => $activity->mostTravelled(),
            'busiestCostume' => $activity->busiestCostume(),
            'weeks'          => $activity->weeklyActivity(),
        ];

        return view('admin.activity', compact('group', 'stats'));
    }
}
