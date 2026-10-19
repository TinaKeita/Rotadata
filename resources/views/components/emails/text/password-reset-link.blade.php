{{-- vienkāršā teksta versija e-pasta programmām bez HTML --}}
Hi {!! $user->name !!},

Someone asked to reset the password for your Rotadata account. Open this link to choose a new one (it works for {{ $minutes }} minutes):

{!! $url !!}

If you didn't ask for this, you can ignore this email; your password stays the same.

--
Rotadata — costume inventory management
Questions? Just reply to this email.
