{{-- vienkāršā teksta versija e-pasta programmām bez HTML --}}
Hi {!! $user->name !!},

{!! $takenBy->name !!} scanned and took over {!! $item->code !!} ({!! $item->costume->name !!}), which was in your name. It is no longer on your list.

If you handed it over, there's nothing else to do. If you didn't, tell your teacher — they can see every handover and give the item back to you.

Open Rotadata: {!! route('login') !!}

--
Your teacher has been notified of this handover too.
Rotadata — costume inventory management
