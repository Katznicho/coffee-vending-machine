<x-app-layout>
    <x-dashboard-layout title="Orders">
        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-gray-600">Orders created by machines, plus any you create manually to trigger a Mobile Money prompt.</p>
            <a href="{{ route('orders.create') }}" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                Create order
            </a>
        </div>

        <div class="app-panel">
            @livewire('orders-table')
        </div>
    </x-dashboard-layout>
</x-app-layout>
