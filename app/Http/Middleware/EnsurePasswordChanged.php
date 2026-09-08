<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    // ja lietotājam vēl jāmaina pagaidu parole, novirza uz paroles maiņas lapu
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $allowed = $request->routeIs('password.change', 'password.change.update', 'logout');

        if ($user && $user->must_change_password && ! $allowed) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
