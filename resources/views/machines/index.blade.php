<x-app-layout>
    <x-dashboard-layout title="Machines">
        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-gray-600">Configure vending machines and their secret keys for API signing.</p>
            <a href="{{ route('machines.create') }}" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                Add machine
            </a>
        </div>

        <div class="app-panel">
            @livewire('machines-table')
        </div>
    </x-dashboard-layout>
</x-app-layout>
