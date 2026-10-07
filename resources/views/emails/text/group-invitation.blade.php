{{-- vienkāršā teksta versija e-pasta programmām bez HTML --}}
Hi {!! $user->name !!},

{!! $invitation->inviter?->name ?? 'A teacher' !!} invited you to join the group "{!! $invitation->group->name !!}" in Rotadata. Sign in with your usual password to accept or decline. Until you accept, you're not part of the group and the teacher can't see your account.

The invitation is open until {!! $invitation->expires_at->format('d.m.Y') !!}.

See the invitation: {!! route('dashboard') !!}

--
If you don't know this teacher, decline the invitation or simply ignore this email.
Rotadata — costume inventory management
