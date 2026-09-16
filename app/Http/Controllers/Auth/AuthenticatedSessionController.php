<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return $this->redirectToDashboard(auth()->user());
    }

    /**
     * Atjauno pašam savu, iepriekš dzēstu kontu, ja parole joprojām sakrīt, un uzreiz pieslēdz.
     */
    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::onlyTrashed()
            ->whereNull('deactivated_with_group_id')
            ->where('email', $request->string('email'))
            ->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user->restore();

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectToDashboard($user);
    }

    private function redirectToDashboard(User $user): RedirectResponse
    {
        if ($user->hasRole('admin')) {
            return redirect('/admin/dashboard');  // administratora sākumlapa
        }

        return redirect('/dashboard');  // dalībnieka sākumlapa
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
