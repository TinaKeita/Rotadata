<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class ForcePasswordController extends Controller
{
    // parāda obligātās paroles maiņas formu
    public function edit(): View
    {
        return view('auth.force-password');
    }

    // saglabā jauno paroli un noņem pagaidu paroles atzīmi
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // vārds jau aizpildīts ar to, ko ievadīja skolotājs – students to var izlabot, bet nav obligāti
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'name' => trim($validated['name']),
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'temporary_password_expires_at' => null,
        ]);

        return redirect()->route('dashboard')->with('success', 'Password changed.');
    }
}
