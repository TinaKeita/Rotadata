{{-- vienkāršā teksta versija e-pasta programmām bez HTML --}}
Hi {!! $user->name !!},

@if($groupName)
Your teacher set up an account for you in the group "{!! $groupName !!}" to manage the costume inventory.
@else
Your teacher set up an account for you to manage the costume inventory.
@endif

Email: {!! $user->email !!}
Temporary password: {!! $password !!}

This password only works for your first sign-in. After that you'll be asked to set your own.

Sign in here: {!! route('login', ['email' => $user->email]) !!}

--
Rotadata — costume inventory management
Questions? Just reply to this email.
