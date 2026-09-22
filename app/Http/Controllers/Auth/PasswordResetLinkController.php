<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // studentiem (role "member") pašapkalpošanās parole atiestatīšana pa e-pastu ir izslēgta –
        // vienīgais ceļš ir lūgt skolotājam atiestatīt no admin paneļa (admin.members.reset-password).
        // Tas novērš divus paralēlus atiestatīšanas ceļus vienam kontam un nepaļaujas uz skolēnu e-pasta
        // piegādi, kas jau zināmi neuzticama (skat. invite_email_failed_at).
        $user = User::where('email', $request->input('email'))->first();

        if ($user && $user->hasRole('member')) {
            // atsevišķa 'notice' atslēga (nevis $errors), lai skats to var parādīt kā skaidru,
            // uzkrītošu paziņojumu, nevis mazu, viegli pamanāmu validācijas kļūdu zem lauka
            return back()->withInput($request->only('email'))
                ->with('notice', "Students can't reset their password here — ask your teacher to reset it from their dashboard.");
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
