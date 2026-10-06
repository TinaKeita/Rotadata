<x-app-layout>
    {{-- konkrēta studenta skatīšanas lapa --}}
    <x-slot name="header">
        <x-page-header eyebrow="Member" :title="$user->name" :subtitle="$user->email" />
    </x-slot>

    <div class="mb-5">
        <a href="{{ route('admin.members.index') }}"
            class="ui-btn-ghost ui-btn-sm">
            Back to Members
        </a>
    </div>

    {{-- uzaicinājuma e-pasts neizdevās nosūtīt – ļauj mēģināt vēlreiz --}}
    @if($user->invite_email_failed_at)
        <div class="ui-alert ui-alert-warn mb-5 flex flex-wrap items-center justify-between gap-3">
            <span>
                The invitation email could not be delivered ({{ $user->invite_email_failed_at->format('d.m.Y H:i') }}).
                @if($user->must_change_password)
                    They still can't have signed in yet.
                @endif
            </span>
            @if($user->must_change_password)
                <form action="{{ route('admin.members.resend-invite', $user) }}" method="POST">
                    @csrf
                    <button type="submit" class="ui-btn-ghost ui-btn-sm">
                        Resend invite
                    </button>
                </form>
            @endif
        </div>
    @endif

    <div class="ui-card max-w-2xl">
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-4 border-b border-line pb-3">
                <dt class="font-medium text-ink-muted">Name</dt>
                <dd class="font-semibold text-ink">{{ $user->name }}</dd>
            </div>

            <div class="flex items-center justify-between gap-4 border-b border-line pb-3">
                <dt class="font-medium text-ink-muted">Email</dt>
                <dd class="text-ink">{{ $user->email }}</dd>
            </div>

            <div class="flex items-center justify-between gap-4">
                <dt class="font-medium text-ink-muted">Created</dt>
                <dd class="text-ink">{{ $user->created_at?->format('d.m.Y') }}</dd>
            </div>

            {{-- tērpu komplekts šajā grupā – nosaka, kuri koncerta tērpi studentam vajadzīgi --}}
            @if($sets->isNotEmpty())
                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line pt-3">
                    <dt class="font-medium text-ink-muted">Set</dt>
                    <dd>
                        <form method="POST" action="{{ route('admin.members.set') }}" class="flex items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                            <select name="costume_set_id" aria-label="Set"
                                class="rounded-lg border-line-strong bg-paper py-1.5 text-sm text-ink focus:border-brand focus:ring-brand/20">
                                <option value="">No set</option>
                                @foreach($sets as $set)
                                    <option value="{{ $set->id }}" @selected((int) $currentSetId === $set->id)>{{ $set->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="ui-btn-ghost ui-btn-sm">
                                Save
                            </button>
                        </form>
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- paroli maina tikai pats dalībnieks ar e-pasta saiti – skolotājs paroli neredz un nemaina --}}
    <div class="ui-card mt-5 max-w-2xl">
        <h3 class="ui-eyebrow">Forgot password?</h3>
        <p class="mt-1 text-sm text-ink-muted">
            {{ $user->name }} can reset it with “Forgot your password?” on the sign-in page. They'll get a link by email to choose a new one.
        </p>
    </div>

    @php
        $currentlyHolds = $user->costumeAssignments->whereNull('returned_at');
        $pastItems = $user->costumeAssignments->whereNotNull('returned_at');
    @endphp

    {{-- skolotājs izsniedz tērpu pats (bez QR skenēšanas): tiek dota nākamā brīvā vienība --}}
    <div id="hand-out" class="ui-card mt-5 max-w-2xl scroll-mt-24">
        <h3 class="ui-eyebrow">Hand out a costume</h3>
        @if($costumes->isEmpty())
            <p class="mt-2 text-sm text-ink-soft">Your group has no costumes yet.</p>
        @else
            <form method="POST" action="{{ route('admin.members.hand-out', $user) }}" class="mt-3 flex flex-wrap items-center gap-2">
                @csrf
                <select name="item_id" required aria-label="Item to hand out"
                    class="min-w-0 flex-1 rounded-lg border-line-strong bg-paper py-2 text-sm text-ink focus:border-brand focus:ring-brand/20">
                    <option value="" disabled selected>Choose item by code…</option>
                    @foreach($costumes as $costume)
                        <optgroup label="{{ $costume->name }}{{ $costume->items->isEmpty() ? ' — none free' : '' }}">
                            @foreach($costume->items as $item)
                                <option value="{{ $item->id }}">{{ $item->code }} · {{ $costume->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <button type="submit" class="ui-btn">Hand out</button>
            </form>
            <p class="ui-help mt-2">Pick the code printed on the label of the item you're giving {{ $user->name }}, so the app matches what they actually have.</p>
        @endif
    </div>

    <div class="ui-card mt-5 max-w-2xl">
        <h3 class="ui-eyebrow">Currently holds ({{ $currentlyHolds->count() }})</h3>
        @if($currentlyHolds->isEmpty())
            <p class="mt-2 text-sm text-ink-soft">Nothing checked out.</p>
        @else
            <ul class="mt-2 space-y-2 text-sm">
                @foreach($currentlyHolds as $log)
                    {{-- kods un tērps, datums zem tā; poga labajā pusē (kā studenta sarakstā) --}}
                    <li class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-medium text-ink">
                                {{ $log->item?->code ?? '—' }}
                                <span class="text-ink-soft">· {{ $log->item?->costume?->name ?? 'deleted costume' }}</span>
                            </p>
                            <p class="font-mono text-[12px] text-ink-soft">since {{ $log->assigned_at->format('d.m.Y') }}</p>
                        </div>
                        {{-- skolotājs paņem vienību atpakaļ (tas pats, kas tērpa lapā) --}}
                        @if($log->item)
                            <form method="POST" action="{{ route('admin.costumes.items.unassign', $log->item) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="ui-btn-ghost ui-btn-sm">Take back</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="ui-card mt-5 max-w-2xl">
        <h3 class="ui-eyebrow">Past items ({{ $pastItems->count() }})</h3>
        @if($pastItems->isEmpty())
            <p class="mt-2 text-sm text-ink-soft">No returned items yet.</p>
        @else
            <ul class="mt-2 space-y-2 text-sm">
                @foreach($pastItems as $log)
                    <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                        <span class="font-medium text-ink">
                            {{ $log->item?->code ?? '—' }}
                            <span class="text-ink-soft">· {{ $log->item?->costume?->name ?? 'deleted costume' }}</span>
                        </span>
                        <span class="text-ink-soft">
                            {{ $log->assigned_at->format('d.m.Y') }} &rarr; {{ $log->returned_at->format('d.m.Y') }}
                            @if($log->return_note === 'admin')
                                <span class="text-ink-soft">(taken back)</span>
                            @elseif($log->return_note === 'transfer')
                                <span class="text-ink-soft">(handed over)</span>
                            @elseif($log->return_note === 'left_group')
                                <span class="text-ink-soft">(left the group)</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
