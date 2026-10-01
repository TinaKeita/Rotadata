{{-- vienkāršā teksta versija e-pasta programmām bez HTML --}}
Hi {!! $user->name !!},

@if($groupName)
Your teacher reset your password for "{!! $groupName !!}" on Rotadata. Your old password no longer works.
@else
Your teacher reset your Rotadata password. Your old password no longer works.
@endif

Email: {!! $user->email !!}
Temporary password: {!! $password !!}

This password only works for your next sign-in. After that you'll be asked to set your own.

Sign in here: {!! route('login', ['email' => $user->email]) !!}

--
Rotadata — costume inventory management
Questions? Just reply to this email.
