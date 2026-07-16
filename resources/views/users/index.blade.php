<x-app-layout>
    <x-dashboard-layout title="Users">
        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-gray-600">Manage who can sign in and administer this system.</p>
            <a href="{{ route('users.create') }}" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                Add user
            </a>
        </div>

        <div class="app-panel">
            @livewire('users-table')
        </div>
    </x-dashboard-layout>
</x-app-layout>
