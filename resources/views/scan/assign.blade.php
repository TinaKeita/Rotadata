<x-guest-layout>
    {{-- tērpa vienības pievienošanas lapa --}}
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light">Assign Costume Item</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Select a student name to assign this scanned item.</p>
    </div>

    <form method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Member</label>
            <select id="user_id" name="user_id" required class="mt-1.5 w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                @foreach($members as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
            Assign
        </button>
    </form>
</x-guest-layout>
