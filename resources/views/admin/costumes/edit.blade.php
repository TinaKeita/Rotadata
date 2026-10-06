<x-app-layout>
    {{-- tērpa nosaukuma rediģēšana --}}
    <x-slot name="header">
        <x-page-header :eyebrow="$costume->name" title="Edit costume" subtitle="Change the costume name, set or photo." />
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.costumes.show', $costume) }}" class="ui-back">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to inventory
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

        <form method="POST" action="{{ route('admin.costumes.update', $costume) }}" class="space-y-5" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="ui-label">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $costume->name) }}" required autocomplete="off"
                    class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft">
            </div>

            {{-- komplekts: tukšs nozīmē kopīgu tērpu, ko koncertā vajag visiem --}}
            @if($sets->isNotEmpty())
                <div>
                    <label for="costume_set_id" class="ui-label">Set</label>
                    <select name="costume_set_id" id="costume_set_id"
                        class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft">
                        <option value="">Shared – everyone needs it</option>
                        @foreach($sets as $set)
                            <option value="{{ $set->id }}" @selected((string) old('costume_set_id', $costume->costume_set_id) === (string) $set->id)>{{ $set->name }} only</option>
                        @endforeach
                    </select>
                    <p class="ui-help mt-1">Only used for concert readiness. Anyone can still scan it.</p>
                </div>
            @endif

            <p class="ui-help">
                Item codes (like <span class="font-semibold">{{ $costume->code_prefix }}-01</span>) and printed QR labels stay the same when you rename.
            </p>

            <div>
                <label class="ui-label">Photo</label>

                @if($costume->image)
                    <div class="mt-2 flex items-center gap-3">
                        <img src="{{ $costume->imageUrl() }}" alt="" class="h-16 w-16 rounded-lg border border-line object-cover">
                        <label class="inline-flex items-center gap-2 text-sm text-ink-muted">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-line-strong text-danger focus:ring-danger/30">
                            Remove photo
                        </label>
                    </div>
                @endif

                <input type="file" name="image" id="image" accept="image/png,image/jpeg,image/webp"
                    class="mt-1.5 block w-full text-sm text-ink-muted file:mr-3 file:rounded-full file:border file:border-solid file:border-line-strong file:bg-transparent file:px-4 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:border-brand hover:file:text-brand">
                <p class="ui-help mt-1">Uploading a new photo replaces the current one. JPG, PNG or WEBP, up to 4 MB.</p>
            </div>

            <button class="ui-btn">
                Save
            </button>
        </form>
    </div>
</x-app-layout>
