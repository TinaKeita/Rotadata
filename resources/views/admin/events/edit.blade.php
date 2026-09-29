<x-app-layout>
    <x-slot name="header">
        <x-page-header eyebrow="Edit concert" :title="$event->title" :subtitle="$event->starts_at->format('l, d.m.Y · H:i')" />
    </x-slot>

    <div class="ui-card max-w-2xl">
        @if ($errors->any())
            <div class="ui-alert ui-alert-error mb-5">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.events.update', $event) }}" class="space-y-5">
            @csrf
            @method('PUT')

            @include('admin.events._form')

            <button class="ui-btn">
                Save changes
            </button>
        </form>
    </div>
</x-app-layout>
