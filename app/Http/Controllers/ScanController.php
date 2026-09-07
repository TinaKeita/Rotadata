<?php

namespace App\Http\Controllers;

use App\Models\CostumeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ScanController extends Controller
{
    // parāda noskenēto tērpa vienību un nākamo soli atkarībā no stāvokļa
    public function show($code)
    {
        $item = CostumeItem::with(['costume.group', 'user'])
            ->where('qr_code', $code)
            ->firstOrFail();

        if ($item->assigned_to) {
            return view('scan.assigned', compact('item'));
        }

        if (! Auth::check()) {
            return view('scan.authenticate', compact('item'));
        }

        // pieslēdzies, bet nav šīs grupas dalībnieks
        if (! Auth::user()->inGroup($item->costume->group)) {
            return view('scan.denied', compact('item'));
        }

        return view('scan.confirm', compact('item'));
    }

    // pārbauda lietotāja paroli un pieslēdz viņu, lai zinātu, kas skenē
    public function authenticate(Request $request, $code)
    {
        $item = CostumeItem::with('costume.group')->where('qr_code', $code)->firstOrFail();

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // tikai šīs grupas dalībnieks drīkst turpināt
        if (! Auth::user()->inGroup($item->costume->group)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'You are not a member of this costume\'s group.',
            ]);
        }

        $request->session()->regenerate();

        return redirect("/scan/{$code}");
    }

    // piešķir tērpa vienību pašam pieslēgtajam lietotājam
    public function assign(Request $request, $code)
    {
        $item = CostumeItem::with('costume.group')->where('qr_code', $code)->firstOrFail();

        if (! Auth::check()) {
            return redirect("/scan/{$code}");
        }

        if ($item->assigned_to) {
            $item->load(['costume.group', 'user']);

            return view('scan.assigned', compact('item'));
        }

        $this->authorize('claim', $item);

        $item->update([
            'assigned_to' => Auth::id(),
            'assigned_at' => now(),
        ]);

        return view('scan.success', compact('item'));
    }
}
