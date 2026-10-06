<x-app-layout>
    <x-slot name="header">
        <x-page-header eyebrow="Account" :title="__('Profile')" subtitle="Manage your account details and password." />
    </x-slot>

    <div>
        <div class="max-w-2xl space-y-6">
            <div class="ui-card">
                <div>
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- skolotāja grupas: atvērt kādu no tām vai izveidot vēl vienu --}}
            @if(auth()->user()->hasRole('admin'))
                <div id="groups" class="ui-card scroll-mt-24">
                    <h2 class="ui-heading">Your groups</h2>
                    <p class="mt-1 text-sm text-ink-muted">You can run more than one group. The one you're working in is shown in the menu; click a group's name there to switch.</p>

                    @php $ownGroups = auth()->user()->adminGroups()->withCount('members')->orderBy('id')->get(); @endphp
                    <div class="mt-4">
                        @forelse($ownGroups as $g)
                            <div class="ui-row">
                                {{-- nosaukums savā rindā, skaits un "current" zem tā --}}
                                <div class="min-w-0">
                                    <p class="font-medium">{{ $g->name }}</p>
                                    <p class="mt-0.5 flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-[12px] text-ink-soft">{{ $g->members_count }} {{ Str::plural('student', $g->members_count) }}</span>
                                        @if(auth()->user()->currentGroup()?->id === $g->id)
                                            <span class="ui-chip ui-chip-good">current</span>
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ route('admin.groups.open', $g) }}" class="ui-btn-ghost ui-btn-sm">Open</a>
                            </div>
                        @empty
                            <p class="text-sm text-ink-soft">You don't run any group right now.</p>
                        @endforelse
                    </div>

                    {{-- nesen dzēstas grupas – atjaunojamas 30 dienu laikā grupas iestatījumos --}}
                    @php $deletedGroups = auth()->user()->adminGroups()->onlyTrashed()->latest('deleted_at')->get(); @endphp
                    @if($deletedGroups->isNotEmpty())
                        <p class="ui-eyebrow mt-5 mb-1">Recently deleted</p>
                        @foreach($deletedGroups as $g)
                            <div class="ui-row">
                                <span class="min-w-0">
                                    <span class="font-medium">{{ $g->name }}</span>
                                    <span class="font-mono text-[12px] text-ink-soft">· erased {{ $g->purgeAt()->format('d.m.Y') }}</span>
                                </span>
                                @if($loop->first)
                                    <a href="{{ route('admin.group.settings') }}" class="ui-btn-ghost ui-btn-sm">Restore…</a>
                                @endif
                            </div>
                        @endforeach
                    @endif

            {{-- grupas, kurās lietotājs ir dalībnieks (piem. skolotājs citas grupas sastāvā) – agrāk konta izvēlnē --}}
            @php $memberOf = auth()->user()->memberGroups()->orderBy('name')->get(); @endphp
            @if(auth()->user()->hasRole('admin') && $memberOf->isNotEmpty())
                <div class="ui-card">
                    <h2 class="ui-heading">Groups you're a member of</h2>
                    <div class="mt-3">
                        @foreach($memberOf as $g)
                            <div class="ui-row">
                                <span class="font-medium">{{ $g->name }}</span>
                                <a href="{{ route('members.costumes.index', $g->id) }}" class="ui-btn-ghost ui-btn-sm">My inventory</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

                    <form method="POST" action="{{ route('admin.groups.store') }}" class="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-4">
                        @csrf
                        <input type="text" name="group_name" value="{{ old('group_name') }}" required maxlength="255" placeholder="New group name, e.g. Folkloras kopa 2B" aria-label="New group name"
                            class="ui-input min-w-0 flex-1 !py-2 !text-sm">
                        <button type="submit" class="ui-btn">Add group</button>
                        @if($errors->newGroup->any())
                            <p class="w-full text-sm text-danger">{{ $errors->newGroup->first('group_name') }}</p>
                        @endif
                    </form>
                </div>
            @endif

            <div class="ui-card">
                <div>
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="ui-card">
                <div>
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
