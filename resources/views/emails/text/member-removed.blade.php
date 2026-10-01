{{-- vienkāršā teksta versija e-pasta programmām bez HTML --}}
Hi {!! $user->name !!},

Your teacher removed you from the group "{!! $groupName !!}" in Rotadata. Any costume items you still had from this group have been marked as returned.

@if($restoreUntil)
This was your only group, so your account is paused. Your teacher can restore it until {!! $restoreUntil !!} if this was a mistake.
@else
Your account still works for your other groups. Sign in with your usual password: {!! route('login') !!}
@endif

--
Rotadata — costume inventory management
Questions? Just reply to this email.
