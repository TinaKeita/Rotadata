{{-- paroles lauks ar "acs" pogu, kas parāda/paslēpj ievadīto paroli; visi atribūti (id, name, autocomplete, x-bind) nonāk pašā input --}}
@php
    // atstarpes un platums pieder ietvaram, citādi acs poga (inset-y-0) nobīdās no lauka
    $classes = collect(preg_split('/\s+/', trim((string) $attributes->get('class', ''))))->filter();
    $isOuter = fn ($c) => (bool) preg_match('/^(-?m[tbxylr]?-|w-|max-w-|min-w-|block$|flex-1$)/', $c);
    $outer = $classes->filter($isOuter)->implode(' ');
    $inner = $classes->reject($isOuter)->implode(' ');
@endphp
<div class="relative {{ $outer }}" x-data="{ show: false }">
    <input type="password" :type="show ? 'text' : 'password'" {{ $attributes->except('class')->merge(['class' => trim('w-full pr-11 '.$inner)]) }}>

    <button type="button" x-on:click="show = !show"
        :aria-label="show ? 'Hide password' : 'Show password'" aria-label="Show password"
        :aria-pressed="show.toString()" aria-pressed="false"
        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-ink-soft hover:text-ink focus:outline-none focus-visible:text-brand">
        {{-- atvērta acs: parole paslēpta, klikšķis to parādīs --}}
        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        {{-- pārsvītrota acs: parole redzama, klikšķis to paslēps --}}
        <svg x-show="show" style="display: none" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.9 3.9"/>
            <path d="M6.6 6.6C3.8 8.4 2 12 2 12s3.5 7 10 7a10.4 10.4 0 0 0 5.4-1.6"/>
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
            <path d="M3 3l18 18"/>
        </svg>
    </button>
</div>
