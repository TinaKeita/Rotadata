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

        // saiti sūta tikai uz pašu e-pasta adresi, tāpēc paroli var nomainīt tikai tas, kurš lasa šo pastkastīti.
        // Atbilde vienmēr ir vienāda, lai lapa neatklātu, vai šāds konts vispār eksistē
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If an account exists for that email, we have sent a link to reset the password.');
    }
}
