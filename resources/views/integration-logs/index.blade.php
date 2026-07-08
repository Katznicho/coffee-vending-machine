<x-app-layout>
    <x-dashboard-layout title="Integration logs">
        <div class="mb-4">
            <p class="text-sm text-gray-600">
                Machine API requests, Cellulant API calls, and payment status sync events. Passwords and tokens are redacted.
            </p>
        </div>

        <div class="app-panel">
            @livewire('integration-logs-table')
        </div>
    </x-dashboard-layout>
</x-app-layout>
