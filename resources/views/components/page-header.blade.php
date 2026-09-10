@props(['title', 'subtitle' => null])

{{-- vienots lapas virsraksts – lieto to visās iekšējās lapās, lai augšējā josla izskatās vienādi --}}
<div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="flex min-w-0 flex-col gap-1">
        <h1 class="truncate text-xl font-semibold leading-tight text-brand-accent dark:text-brand-light sm:text-2xl">
            {{ $title }}
        </h1>

        @if($subtitle)
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        {{-- neobligāta darbību josla virsraksta labajā pusē --}}
        <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
