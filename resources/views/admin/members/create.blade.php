<x-app-layout>
    {{-- pievieno vienu vai vairākus studentus vienā reizē --}}
    <x-slot name="header">
        <x-page-header :eyebrow="auth()->user()->currentGroup()?->name" title="Add students" subtitle="Add one or more students to your group — new or existing." />
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.members.index') }}" class="ui-back">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Members
        </a>
    </div>

    <div class="ui-card max-w-2xl">
        {{-- validācijas kļūdas --}}
        @if ($errors->any())
            <div class="ui-alert ui-alert-error mb-5">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.members.store') }}" class="space-y-5"
            x-data="{ rows: @js(old('members', [['name' => '', 'email' => '']])) }">
            @csrf

            <div class="space-y-3">
                <template x-for="(row, index) in rows" :key="index">
                    {{-- telefonā vārds un e-pasts viens zem otra, lai abiem pietiek vietas --}}
                    <div class="flex flex-wrap items-start gap-3 rounded-lg border border-line p-3">
                        <div class="min-w-0 basis-full sm:flex-1">
                            <label class="ui-label">Name</label>
                            <input type="text" :name="`members[${index}][name]`" x-model="row.name" required autocomplete="off"
                                class="ui-input mt-1 !py-2 !text-sm">
                        </div>
                        <div class="min-w-0 basis-full sm:flex-1">
                            <label class="ui-label">Email</label>
                            <input type="email" :name="`members[${index}][email]`" x-model="row.email" required autocomplete="off"
                                class="ui-input mt-1 !py-2 !text-sm">
                        </div>
                        <button type="button" x-show="rows.length > 1" @click="rows.splice(index, 1)"
                            class="ui-btn-danger ui-btn-sm shrink-0 sm:mt-6">
                            Remove
                        </button>
                    </div>
                </template>
            </div>

            <button type="button" @click="rows.push({ name: '', email: '' })"
                class="ui-btn-ghost ui-btn-sm">
                + Add another student
            </button>

            <p class="ui-help">
                If someone already uses Rotadata, they're just added to your group and emailed — no new password, and the name above is ignored for them.
            </p>

            {{-- viens komplekts visai partijai, piem. vispirms visas meitenes, tad visi puiši --}}
            @if($sets->isNotEmpty())
                <div>
                    <label for="costume_set_id" class="ui-label">Set for everyone above <span class="font-normal text-ink-soft">(optional)</span></label>
                    <select name="costume_set_id" id="costume_set_id"
                        class="ui-input mt-1.5 max-w-xs">
                        <option value="">No set</option>
                        @foreach($sets as $set)
                            <option value="{{ $set->id }}" @selected((string) old('costume_set_id') === (string) $set->id)>{{ $set->name }}</option>
                        @endforeach
                    </select>
                    <p class="ui-help mt-1">Decides which costumes they need for concerts. You can change it later on the Members page.</p>
                </div>
            @endif

            <button type="submit" class="ui-btn">
                <span>Add</span>
                <span x-text="`${rows.length} ${rows.length === 1 ? 'student' : 'students'}`"></span>
            </button>
        </form>
    </div>
</x-app-layout>
