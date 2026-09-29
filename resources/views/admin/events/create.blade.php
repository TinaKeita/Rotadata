<x-app-layout>
    <x-slot name="header">
        <x-page-header :eyebrow="auth()->user()->adminGroups()->value('name')" title="Add concert" subtitle="Schedule a new concert for your group." />
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

        <form method="POST" action="{{ route('admin.events.store') }}" class="space-y-5">
            @csrf

            @include('admin.events._form')

            <button class="ui-btn">
                Create
            </button>
        </form>
    </div>
</x-app-layout>
