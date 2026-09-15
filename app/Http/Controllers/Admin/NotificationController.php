<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    // atzīmē vienu paziņojumu kā izlasītu – relācija pati nodrošina, ka skolotājs var
    // aizvērt tikai SAVus paziņojumus
    public function dismiss(string $notification)
    {
        auth()->user()->notifications()->whereKey($notification)->first()?->markAsRead();

        return back();
    }
}
