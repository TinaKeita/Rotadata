<x-app-layout>
    {{-- saņēmēja lapa: cits skolotājs vēlas nodot savu grupu – pieņemt vai noraidīt --}}
    <x-slot name="header">
        <x-page-header eyebrow="Group handover" :title="$transfer->group?->name ?? 'Group handover'"
            :subtitle="$transfer->fromUser->name.' wants to hand this group over to you.'" />
    </x-slot>

    <div class="max-w-2xl space-y-5">
        @if(! $transfer->isOpen())
            <p class="ui-alert ui-alert-warn">
                @switch($transfer->status)
                    @case('accepted') You already accepted this request. @break
                    @case('declined') You declined this request. @break
                    @case('cancelled') {{ $transfer->fromUser->name }} cancelled this request. @break
                    @default This request expired on {{ $transfer->expires_at->format('d.m.Y') }}.
                @endswitch
            </p>
        @else
            <section class="ui-card">
                <h2 class="ui-eyebrow mb-3">What you'd take over</h2>
                @if($impact)
                    <div class="ui-row"><span class="text-ink-muted">Students</span><span class="font-mono">{{ $impact['students'] }}</span></div>
                    <div class="ui-row"><span class="text-ink-muted">Costumes</span><span class="font-mono">{{ $impact['costumes'] }}</span></div>
                    <div class="ui-row"><span class="text-ink-muted">Items</span><span class="font-mono">{{ $impact['items'] }} · {{ $impact['itemsOut'] }} out</span></div>
                    <div class="ui-row"><span class="text-ink-muted">Upcoming concerts</span><span class="font-mono">{{ $impact['upcoming'] }}</span></div>
                @endif
                <p class="ui-help mt-4">
                    You become the group's teacher with its whole history. {{ $transfer->fromUser->name }} will no longer manage it.
                    Your other groups stay as they are. Open until {{ $transfer->expires_at->format('d.m.Y') }}.
                </p>
            </section>

            <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.group.transfer.accept', $transfer->token) }}"
                        onsubmit="return confirm('Take over “{{ $transfer->group->name }}”?');">
                        @csrf
                        <button type="submit" class="ui-btn">Accept and take over</button>
                    </form>
                <form method="POST" action="{{ route('admin.group.transfer.decline', $transfer->token) }}">
                    @csrf
                    <button type="submit" class="ui-btn-ghost">Decline</button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
