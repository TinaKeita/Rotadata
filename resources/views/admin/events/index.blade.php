<x-app-layout>
    <x-slot name="header">
        <x-page-header :eyebrow="auth()->user()->currentGroup()?->name" title="Concerts" subtitle="Schedule concerts and let students know what's needed.">
            <x-slot:actions>
                <a href="{{ route('admin.events.create') }}"
                    class="ui-btn ui-btn-sm">
                    Add concert
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-8">
        <section>
            <h3 class="ui-eyebrow mb-3">Upcoming</h3>
            <div class="space-y-3">
                @forelse($upcoming as $event)
                    @include('admin.events._row', ['event' => $event])
                @empty
                    <p class="rounded-[14px] border border-dashed border-line-strong px-4 py-8 text-center text-sm text-ink-soft">
                        No upcoming concerts. Add one to get started.
                    </p>
                @endforelse
            </div>
        </section>

        @if($past->isNotEmpty())
            <section>
                <h3 class="ui-eyebrow mb-3">Past</h3>
                <div class="space-y-3">
                    @foreach($past as $event)
                        @include('admin.events._row', ['event' => $event])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
