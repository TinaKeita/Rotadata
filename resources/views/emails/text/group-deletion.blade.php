{{-- vienkāršā teksta versija e-pasta programmām bez HTML --}}
Hi {!! $user->name !!},

@if($variant === 'deactivated')
Your teacher deleted the group "{!! $groupName !!}". It was your only group, so your Rotadata account is now inactive and you can't sign in for the moment.

If this was a mistake, your teacher can restore the group and your account until {!! $purgeDate !!}. After that date, the account and all its data are permanently deleted.
@elseif($variant === 'removed')
Your teacher deleted the group "{!! $groupName !!}". You're in other groups too, so your account keeps working as normal. The only change is that this group and its costumes are no longer available.

Sign in here: {!! route('login') !!}
@else
Good news: the group "{!! $groupName !!}" has been restored and your account is active again. All costumes, assignments and history were kept.

Sign in here: {!! route('login') !!}
@endif

--
Rotadata — costume inventory management
Questions? Just reply to this email.
