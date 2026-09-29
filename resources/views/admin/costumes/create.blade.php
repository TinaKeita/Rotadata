<x-app-layout>
    {{-- pievieno jaunu tērpu --}}
    <x-slot name="header">
        <x-page-header :eyebrow="auth()->user()->adminGroups()->value('name')" title="Add costume" subtitle="Add a new costume and generate inventory items." />
    </x-slot>

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

        <form method="POST" action="{{ route('admin.costumes.store') }}" class="space-y-5" enctype="multipart/form-data">
            @csrf

            <div>
                <label for="name" class="ui-label">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft" required autocomplete="name">
            </div>

            {{-- komplekts: tukšs nozīmē kopīgu tērpu, ko koncertā vajag visiem --}}
            @if($sets->isNotEmpty())
                <div>
                    <label for="costume_set_id" class="ui-label">Set</label>
                    <select name="costume_set_id" id="costume_set_id"
                        class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft">
                        <option value="">Shared – everyone needs it</option>
                        @foreach($sets as $set)
                            <option value="{{ $set->id }}" @selected((string) old('costume_set_id') === (string) $set->id)>{{ $set->name }} only</option>
                        @endforeach
                    </select>
                    <p class="ui-help mt-1">Only used for concert readiness. Anyone can still scan it.</p>
                </div>
            @endif

            <div>
                <label for="quantity" class="ui-label">Quantity</label>
                <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}" class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft" required min="1" autocomplete="off">
            </div>

            <div>
                <label for="image" class="ui-label">Photo <span class="font-normal text-ink-soft">(optional)</span></label>
                <input type="file" name="image" id="image" accept="image/png,image/jpeg,image/webp"
                    class="mt-1.5 block w-full text-sm text-ink-muted file:mr-3 file:rounded-full file:border file:border-solid file:border-line-strong file:bg-transparent file:px-4 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:border-brand hover:file:text-brand">
                <p class="ui-help mt-1">Helps members recognize the right costume when scanning. JPG, PNG or WEBP, up to 4 MB.</p>
            </div>

            <button class="ui-btn">
                Create
            </button>
        </form>
    </div>
</x-app-layout>
