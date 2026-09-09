<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            // vai lietotājs ir kādas grupas skolotājs – tad kontu dzēst nevar
            'ownsGroup' => $request->user()->adminGroups()->exists(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('success', 'Profile updated.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // skolotājs, kas pārvalda grupu, nevar dzēst kontu, kamēr grupa nav nodota citam
        // (groups.admin_id ārējā atslēga citādi izmestu datubāzes kļūdu)
        $ownedGroups = $user->adminGroups()->pluck('name');

        if ($ownedGroups->isNotEmpty()) {
            return Redirect::route('profile.edit')->with(
                'error',
                "You are the teacher of {$ownedGroups->implode(', ')} — your account can't be deleted until the group is handed over to another teacher."
            );
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
