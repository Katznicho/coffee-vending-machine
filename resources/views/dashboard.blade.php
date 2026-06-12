<x-app-layout>
    <x-dashboard-layout title="Dashboard">
        <div class="grid gap-6 md:grid-cols-4">
            <div class="app-card">
                <p class="text-sm text-gray-500">Machines</p>
                <p class="mt-2 text-3xl font-semibold">{{ $machinesCount }}</p>
            </div>
            <div class="app-card">
                <p class="text-sm text-gray-500">Orders today</p>
                <p class="mt-2 text-3xl font-semibold">{{ $ordersToday }}</p>
            </div>
            <div class="app-card">
                <p class="text-sm text-gray-500">Paid today</p>
                <p class="mt-2 text-3xl font-semibold">{{ $paidToday }}</p>
            </div>
            <div class="app-card">
                <p class="text-sm text-gray-500">Revenue today</p>
                <p class="mt-2 text-3xl font-semibold">UGX {{ number_format($revenueToday) }}</p>
            </div>
        </div>

        <div class="app-panel mt-8">
            <div class="px-6 py-4">
                <h2 class="text-lg font-semibold">Recent orders</h2>
            </div>
            <div class="px-2 pb-2">
                @livewire('recent-orders-table')
            </div>
        </div>
    </x-dashboard-layout>
</x-app-layout>
