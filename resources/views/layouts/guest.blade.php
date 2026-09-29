{{-- pārējās guest lapas (QR skenēšana, paroles atjaunošana, e-pasta apstiprināšana) lieto to pašu
     landing stila izkārtojumu kā login/register: kapsulas navigācija, papīra fons, kartīte --}}
<x-auth-layout>
    {{ $slot }}
</x-auth-layout>
