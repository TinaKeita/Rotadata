{{-- uzaicinājumi pievienoties citu skolotāju grupām – lietotājs pats pieņem vai noraida (studenta un skolotāja sākumlapā) --}}
@php
    $openInvitations = auth()->user()->groupInvitations()->open()->with(['group', 'inviter'])->latest()->get();
@endphp

@foreach($openInvitations as $invitation)
    <div class="ui-card mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[15.5px]">
                <span class="font-semibold">{{ $invitation->inviter?->name ?? 'A teacher' }}</span>
                <span class="text-ink-muted">invited you to join</span>
                <span class="font-semibold">{{ $invitation->group->name }}</span>
            </p>
            <p class="mt-0.5 font-mono text-[12px] text-ink-soft">open until {{ $invitation->expires_at->format('d.m.Y') }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <form method="POST" action="{{ route('invitations.accept', $invitation) }}">
                @csrf
                <button type="submit" class="ui-btn ui-btn-sm">Accept</button>
            </form>
            <form method="POST" action="{{ route('invitations.decline', $invitation) }}">
                @csrf
                <button type="submit" class="ui-btn-ghost ui-btn-sm">Decline</button>
            </form>
        </div>
    </div>
@endforeach
