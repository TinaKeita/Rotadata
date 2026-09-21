@php
    // visi lapas teksti un paraugdati vienuviet, lai marķējums zemāk paliek tīrs
    $isAuth = auth()->check();
    $ctaHref = $isAuth ? route('dashboard') : route('login');
    // reģistrācija ir atvērta: jauns skolotājs uzreiz izveido kontu un grupu
    $signupHref = $isAuth ? route('dashboard') : (Route::has('register') ? route('register') : route('login'));

    $navLinks = [
        ['Benefits', '#benefits'],
        ['Compared', '#specs'],
        ['How it works', '#how'],
        ['Contact', '#contact'],
    ];

    // hero kartītes paraugdati; kodi ir PREFIX-NN formātā kā Costume::addItems
    $heroStats = [
        ['Items in the collection', '86'],
        ['Checked out right now', '24'],
        ['Students equipped', '19 / 26'],
    ];

    // lietotnē vienībai ir tikai divi stāvokļi: izsniegta ("Out") vai brīva ("Available")
    $badges = [
        'Out' => 'bg-[#E8EFE9] text-brand',
        'Available' => 'bg-[#F0EEE8] text-ink-soft',
    ];

    $rows = [
        ['VAI-03', 'Vainags, liels', 'Marta Liepa', 'Out'],
        ['BRU-05', 'Brunči nr. 12', 'Laura Bērziņa', 'Out'],
        ['KRE-07', 'Krekls, balts', '—', 'Available'],
        ['JOS-02', 'Josta, austa', 'Roberts Vītols', 'Out'],
        ['ZEK-04', 'Zeķes, vilnas', '—', 'Available'],
    ];

    // PLACEHOLDER: izdomāti skolu nosaukumi – aizstāt ar īstiem vai dzēst visu sadaļu
    $trusted = ['JDK «Virpulis»', 'Daugavpils Mūzikas vidusskola', 'Deju kopa «Pērle»', 'Liepājas teātra studija'];

    $benefits = [
        ['Nothing goes missing', 'Every item has a code and a history. You always know who had it last.', 0],
        ['Students check themselves', 'They scan, they see their own list. No queue at your desk.', 60],
        ['Ready before the show', 'See what is short before the dress rehearsal, not during it.', 120],
        // gada beigu eksports vēl top – skolotāja atskaite tiks pievienota; teksts paliek kā plānots
        ['The year ends cleanly','One export for administration, generated rather than assembled.', 180],
    ];

    $points = [
        ['Group at a glance.', 'Who holds what, and how much is still out.'],
        ['Item history.', 'Every student who ever wore it, in order.'],
        ['QR on the label.', 'Scan to take it, one tap to hand it back.'],
        ['Out the longest.', 'The quiet list that saves you in May.'],
    ];

    $compareLabels = [
        'Live count of what is out',
        'Full history per item',
        'Students see their own list',
        'QR scan to check items out',
        // gada beigu eksports vēl top (skat. iepriekš)
        'Year-end export in one click',
    ];

    $compare = [
        ['Rotadata', [true, true, true, true, true]],
        ['Spreadsheet', [true, false, false, false, false]],
        ['Paper list', [false, false, false, false, false]],
    ];

    $steps = [
        ['Build the inventory', 'Add each costume once with its quantity. Rotadata generates the item codes and labels.', 0],
        // grupu izveido reģistrācijā; skolotājs pievieno studentus ar vārdu un e-pastu (vairākus uzreiz), students saņem pieteikšanās datus e-pastā
        ['Set up your group', 'Sign up as a teacher and name your group. Add students by name and email, a whole class at once, and they get their sign-in by email.', 80],
        ['Scan and track', 'Students scan a label to take a costume and hand it back from their own list. Your dashboard shows every move.', 160],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth motion-reduce:scroll-auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Rotadata keeps track of what your group owns, who is wearing it, and what came back.">
    <title>{{ config('app.name', 'Rotadata') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    {{-- fonti nāk no Bunny Fonts (ES hostings, neseko apmeklētājiem) – tāpat kā pārējā lietotne, nevis no Google --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=newsreader:400|public-sans:400,500,600|ibm-plex-mono:400,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/landing-reveal.js'])
</head>
<body class="overflow-x-clip bg-paper font-body text-base leading-[1.55] text-ink antialiased">

    {{-- peldošā navigācijas kapsula; zem 768px saites paslēptas aiz "Menu" pogas --}}
    <header class="pointer-events-none sticky top-0 z-30 flex justify-center px-4 py-3.5"
        x-data="{ open: false }" @keydown.escape.window="open = false">
        <nav aria-label="Main"
            class="pointer-events-auto relative flex max-w-full items-center gap-2 rounded-full border border-[#E2DED6] bg-paper/80 py-[7px] pl-4 pr-[7px] shadow-nav backdrop-blur-[14px]">
            <a href="#top" class="flex shrink-0 items-center gap-2 text-[15px] font-semibold tracking-[-0.01em] hover:text-brand">
                <img src="{{ asset('favicon.svg') }}" alt="" width="22" height="22" class="block h-[22px] w-[22px]">
                Rotadata
            </a>
            <span class="mx-1 hidden h-[18px] w-px shrink-0 bg-[#E2DED6] md:block"></span>

            <div class="hidden min-w-0 gap-0.5 md:flex">
                @foreach ($navLinks as [$label, $href])
                    <a href="{{ $href }}" class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium text-ink-muted hover:bg-[#EFECE6] hover:text-ink">{{ $label }}</a>
                @endforeach
            </div>

            <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="landing-menu"
                class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium text-ink-muted hover:bg-[#EFECE6] hover:text-ink md:hidden">
                Menu
            </button>

            <a href="{{ $ctaHref }}" class="shrink-0 whitespace-nowrap rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:bg-brand">
                {{ $isAuth ? 'Dashboard' : 'Log in' }}
            </a>

            <div id="landing-menu" x-show="open" @click.outside="open = false" style="display: none"
                class="absolute inset-x-0 top-full mt-2 flex flex-col rounded-2xl border border-[#E2DED6] bg-paper p-2 shadow-nav md:hidden">
                @foreach ($navLinks as [$label, $href])
                    <a href="{{ $href }}" @click="open = false" class="rounded-xl px-3 py-2.5 text-sm font-medium text-ink-muted hover:bg-[#EFECE6] hover:text-ink">{{ $label }}</a>
                @endforeach
            </div>
        </nav>
    </header>

    <main>
        {{-- hero --}}
        <section id="top" class="mx-auto max-w-shell px-6 pt-14 text-center">
            <p data-reveal="" class="mb-5 font-mono text-xs uppercase tracking-[0.1em] text-brand">Costume inventory for schools</p>
            <h1 data-reveal="" data-reveal-delay="60"
                class="mx-auto mb-[22px] max-w-[16ch] text-balance font-display text-[length:clamp(42px,7vw,76px)] font-normal leading-[1.02] tracking-[-0.025em]">Every costume accounted for.</h1>
            <p data-reveal="" data-reveal-delay="120"
                class="mx-auto mb-8 max-w-[52ch] text-pretty text-[length:clamp(17px,1.7vw,19px)] text-ink-muted">Rotadata keeps track of what your group owns, who is wearing it, and what came back — so you can rehearse instead of counting.</p>
            <div data-reveal="" data-reveal-delay="180" class="mb-14 flex flex-wrap justify-center gap-2.5">
                <a href="{{ $signupHref }}" class="rounded-full bg-ink px-[26px] py-[13px] text-[15.5px] font-medium text-paper hover:bg-brand">{{ $isAuth ? 'Go to dashboard' : 'Get started' }}</a>
                <a href="#how" class="rounded-full border border-line-strong px-6 py-[13px] text-[15.5px] font-medium hover:border-brand hover:bg-surface hover:text-brand">See how it works</a>
            </div>
        </section>

        {{-- produkta kartīte – lapas vienīgais "hero attēls" un vienīgā ēna --}}
        <section class="mx-auto max-w-shell px-6">
            <div data-reveal="" data-reveal-delay="220" class="overflow-hidden rounded-block border border-line bg-surface shadow-hero">
                <div class="flex items-center gap-2.5 border-b border-line bg-paper px-[18px] py-3">
                    <span class="flex gap-[5px]" aria-hidden="true">
                        <span class="block h-[9px] w-[9px] rounded-full bg-[#E2DED6]"></span>
                        <span class="block h-[9px] w-[9px] rounded-full bg-[#E2DED6]"></span>
                        <span class="block h-[9px] w-[9px] rounded-full bg-[#E2DED6]"></span>
                    </span>
                    <span class="ml-2 font-mono text-[11.5px] text-ink-soft">Folkloras kopa 4B</span>
                    <span class="ml-auto font-mono text-[11.5px] text-brand">24 / 86 out</span>
                </div>

                <div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-px border-b border-line bg-line">
                    @foreach ($heroStats as [$label, $value])
                        <div class="min-w-0 bg-surface px-5 py-[18px]">
                            <div class="text-[12.5px] text-ink-soft">{{ $label }}</div>
                            <div class="mt-1.5 font-display text-[28px] leading-none">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="py-1.5">
                    @foreach ($rows as [$code, $item, $person, $state])
                        <div class="grid grid-cols-[64px_minmax(0,1fr)_auto] items-center gap-x-3 border-b border-line-soft px-5 py-3 text-[14.5px] sm:grid-cols-[90px_minmax(0,1.6fr)_minmax(0,1fr)_auto] sm:gap-x-3.5">
                            <span class="font-mono text-[13px] font-medium">{{ $code }}</span>
                            {{-- šaurā ekrānā vienība un turētājs stāv vienā šūnā viens zem otra (citādi abi kļūst nesalasāmi), no sm uz augšu – atsevišķas kolonnas --}}
                            <div class="min-w-0 sm:contents">
                                <span class="block truncate">{{ $item }}</span>
                                <span @class(['block truncate text-[13px] text-ink-soft sm:text-[14.5px]', 'max-sm:hidden' => $person === '—'])>{{ $person }}</span>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-[12.5px] font-medium {{ $badges[$state] }}">{{ $state }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- in use at --}}
        <section data-reveal="" class="mx-auto max-w-shell px-6 pt-14 text-center">
            <p class="mb-5 font-mono text-[11px] uppercase tracking-[0.12em] text-ink-soft">
                In use at
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-3.5">
                @foreach ($trusted as $school)
                    <span class="font-display text-[17px] text-ink-soft">{{ $school }}</span>
                @endforeach
            </div>
        </section>

        {{-- 01 Benefits --}}
        <section id="benefits" class="mx-auto max-w-shell px-6 pt-24">
            <x-landing.section-marker number="01" label="Benefits" />
            <div data-reveal="" class="mb-12 max-w-[44ch]">
                <h2 class="mb-3 font-display text-[length:clamp(30px,4vw,44px)] font-normal leading-[1.1] tracking-[-0.02em]">One list everyone trusts.</h2>
                <p class="text-pretty text-[17px] text-ink-muted">No shared spreadsheet, no clipboard in the costume room, no asking around in May.</p>
            </div>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(230px,1fr))] gap-8">
                @foreach ($benefits as $i => [$title, $body, $delay])
                    <x-landing.benefit-card :number="sprintf('%02d', $i + 1)" :title="$title" :delay="$delay">{{ $body }}</x-landing.benefit-card>
                @endforeach
            </div>
        </section>

        {{-- bez numura: "See the whole collection" --}}
        <section class="mx-auto max-w-shell px-6 pt-[88px]">
            {{-- min(320px,100%): ļauj kolonnai sarauties zem 320px, citādi šauros telefonos bloks izvirzās ārpus lapas un tiek nogriezts --}}
            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(320px,100%),1fr))] items-center gap-12">
                {{-- kopbilde ir portreta formātā (9:16), rāmis 5:4 – object-position 62% notēmē uz cilvēkiem, nevis griestiem vai grīdu --}}
                <div data-reveal="" class="relative aspect-[5/4] min-w-0 overflow-hidden rounded-[14px] border border-line bg-surface-sunk">
                    <img src="{{ asset('images/landing/group-rehearsal.jpg') }}" width="900" height="1600" loading="lazy" decoding="async"
                        alt="JDK “Virpulis” youth dance group posing together in a rehearsal hall"
                        class="h-full w-full object-cover object-[50%_62%]">
                    {{-- puscaurspīdīgs paraksts: JDK "Virpulis" ir šīs sistēmas iedvesma --}}
                    <span class="absolute bottom-3 left-3 rounded-md border border-line bg-paper/75 px-2.5 py-1.5 font-mono text-[11.5px] text-ink-soft backdrop-blur-sm">Inspired by JDK “Virpulis”</span>
                </div>

                <div data-reveal="" data-reveal-delay="80" class="min-w-0">
                    <h2 class="mb-3 font-display text-[length:clamp(28px,3.4vw,38px)] font-normal leading-[1.12] tracking-[-0.02em]">See the whole collection</h2>
                    <p class="mb-7 max-w-[44ch] text-pretty text-[16.5px] text-ink-muted">Rotadata turns a cupboard of costumes into something you can actually read.</p>
                    <div>
                        @foreach ($points as $i => [$title, $body])
                            <div class="flex items-baseline gap-4 border-t border-line py-3.5">
                                <span class="shrink-0 font-mono text-[11.5px] text-brand">{{ sprintf('%02d', $i + 1) }}</span>
                                <span class="text-pretty text-[15.5px]"><span class="font-semibold">{{ $title }}</span> <span class="text-ink-muted">{{ $body }}</span></span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- 02 Compared --}}
        <section id="specs" class="mx-auto max-w-shell px-6 pt-24">
            <x-landing.section-marker number="02" label="Compared" />
            <h2 data-reveal="" class="mb-10 max-w-[30ch] font-display text-[length:clamp(30px,4vw,44px)] font-normal leading-[1.1] tracking-[-0.02em]">Why not a spreadsheet?</h2>
            <div data-reveal="" class="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-4">
                @foreach ($compare as [$name, $marks])
                    <x-landing.compare-card :name="$name" :labels="$compareLabels" :marks="$marks" :highlight="$loop->first" />
                @endforeach
            </div>
        </section>

        {{-- atsauksme --}}
        <section class="mx-auto max-w-shell px-6 pt-[88px]">
            <figure data-reveal="" class="grid grid-cols-[repeat(auto-fit,minmax(min(260px,100%),1fr))] items-center gap-8 border-y border-line py-11">
                <blockquote class="min-w-0 text-pretty font-display text-[length:clamp(21px,2.4vw,28px)] leading-[1.35] tracking-[-0.01em] sm:col-span-2">“Before, the costume list lived in my head and three notebooks. Now a student can tell me what they have without me having to ask.”</blockquote>
                <figcaption class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#EDEAE3] text-[13px] font-semibold text-ink-muted">IB</span>
                    <span class="min-w-0">
                        <span class="block text-[15px] font-medium">Ilze Bērziņa</span>
                        <span class="block text-[13.5px] text-ink-soft">Folklore group leader</span>
                    </span>
                </figcaption>
            </figure>
        </section>

        {{-- 03 How it works --}}
        <section id="how" class="mx-auto max-w-shell px-6 pt-[88px]">
            <x-landing.section-marker number="03" label="How it works" />
            <div class="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-9">
                @foreach ($steps as $i => [$title, $body, $delay])
                    <div data-reveal="" data-reveal-delay="{{ $delay }}" class="min-w-0">
                        <div class="mb-3.5 font-display text-[42px] leading-none text-brand">{{ sprintf('%02d', $i + 1) }}</div>
                        <h3 class="mb-2 font-body text-[17.5px] font-semibold tracking-[-0.01em]">{{ $title }}</h3>
                        <p class="text-pretty text-[15.5px] text-ink-muted">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- CTA bloks --}}
        {{-- PĀRBAUDĪT: sataisīt lai info.rotadata.lv dabū rederect no gmail --}}
        <section id="contact" class="mx-auto max-w-shell px-6 pt-[88px]">
            <div data-reveal="" class="rounded-block bg-brand-dark p-[clamp(40px,6vw,72px)] text-center text-[#F3F1EC]">
                <h2 class="mx-auto mb-3.5 max-w-[18ch] text-balance font-display text-[length:clamp(30px,4.4vw,48px)] font-normal leading-[1.08] tracking-[-0.02em]">Start with one group.</h2>
                <p class="mx-auto mb-[30px] max-w-[46ch] text-pretty text-[17px] text-[#C8C4BA]">Set it up in an afternoon, use it all season. Add your costumes, add your students, print the QR labels.</p>
                <div class="flex flex-wrap justify-center gap-2.5">
                    <a href="{{ $signupHref }}" class="rounded-full bg-[#F3F1EC] px-7 py-[13px] text-[15.5px] font-medium text-brand-dark hover:bg-white">{{ $isAuth ? 'Go to dashboard' : 'Create a teacher account' }}</a>
                    <a href="mailto:info@rotadata.lv" class="rounded-full border border-[#F3F1EC]/30 px-[26px] py-[13px] text-[15.5px] font-medium text-[#F3F1EC] hover:border-[#F3F1EC] hover:text-white">Email us</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="mx-auto flex max-w-shell flex-wrap items-center gap-[18px] px-6 pb-14 pt-10 text-[13.5px] text-ink-soft">
        <span class="flex items-center gap-2">
            <img src="{{ asset('favicon.svg') }}" alt="" width="18" height="18" class="block h-[18px] w-[18px]">
            © {{ date('Y') }} Rotadata
        </span>
        <span class="ml-auto flex flex-wrap gap-[22px]">
            <a href="#benefits" class="hover:text-ink">Benefits</a>
            <a href="#specs" class="hover:text-ink">Compared</a>
            <a href="#how" class="hover:text-ink">How it works</a>
            <a href="mailto:info@rotadata.lv" class="hover:text-ink">info@rotadata.lv</a>
        </span>
    </footer>
</body>
</html>
