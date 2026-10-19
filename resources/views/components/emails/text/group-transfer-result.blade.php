{{-- vienkāršā teksta versija e-pasta programmām bez HTML --}}
Hi {!! $transfer->fromUser->name !!},

@if($accepted)
{!! $transfer->toUser->name !!} accepted your request and is now the teacher of "{!! $transfer->group->name !!}". All its students, costumes, concerts and history went with it. You no longer manage this group.
@else
{!! $transfer->toUser->name !!} declined taking over "{!! $transfer->group->name !!}". Nothing has changed, and the group is still yours. You can send a request to someone else from Group settings.
@endif

--
Rotadata — costume inventory management
Questions? Just reply to this email.
