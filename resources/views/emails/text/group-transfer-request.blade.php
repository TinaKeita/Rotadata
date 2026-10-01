{{-- vienkāršā teksta versija e-pasta programmām bez HTML --}}
Hi {!! $transfer->toUser->name !!},

{!! $transfer->fromUser->name !!} wants to hand over their group "{!! $transfer->group->name !!}" to you in Rotadata.

If you accept, you become its teacher and take over everything in it: {{ $impact['students'] }} students, {{ $impact['costumes'] }} costumes ({{ $impact['items'] }} items, {{ $impact['itemsOut'] }} currently out), {{ $impact['upcoming'] }} upcoming concerts and the full history. {!! $transfer->fromUser->name !!} will no longer manage it.

Nothing changes until you accept. The request is open until {{ $transfer->expires_at->format('d.m.Y') }}.

Review and accept or decline (sign in to your teacher account first):
{!! route('admin.group.transfer.show', $transfer->token) !!}

--
Rotadata — costume inventory management
Questions? Just reply to this email.
