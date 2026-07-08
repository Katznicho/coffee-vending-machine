<x-app-layout>
    <x-dashboard-layout title="Log #{{ $integrationLog->id }}">
        <div class="mb-4">
            <a href="{{ route('integration-logs.index') }}" class="text-sm text-brand hover:underline">&larr; Back to integration logs</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Summary</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Time</dt><dd>{{ $integrationLog->created_at->format('M j, Y H:i:s') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Direction</dt><dd>{{ ucfirst($integrationLog->direction) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Channel</dt><dd class="font-mono">{{ $integrationLog->channel }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Event</dt><dd class="font-mono">{{ $integrationLog->event }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Success</dt><dd>{{ $integrationLog->success ? 'Yes' : 'No' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">HTTP status</dt><dd class="font-mono">{{ $integrationLog->http_status ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Duration</dt><dd>{{ $integrationLog->duration_ms !== null ? $integrationLog->duration_ms.' ms' : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Reference</dt><dd class="font-mono">{{ $integrationLog->reference ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Merchant txn</dt><dd class="font-mono">{{ $integrationLog->merchant_transaction_id ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Machine</dt><dd class="font-mono">{{ $integrationLog->machine_id ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Order</dt>
                        <dd>
                            @if($integrationLog->order)
                                <a href="{{ route('orders.show', $integrationLog->order) }}" class="font-mono text-brand hover:underline">{{ $integrationLog->order->third_party_order_id }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">IP address</dt><dd class="font-mono">{{ $integrationLog->ip_address ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Message</dt><dd>{{ $integrationLog->message ?? '—' }}</dd></div>
                    @if($integrationLog->url)
                        <div><dt class="text-gray-500">URL</dt><dd class="mt-1 break-all font-mono text-xs">{{ $integrationLog->url }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Response</h2>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-green-100">{{ json_encode($integrationLog->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>

        <div class="app-card mt-6">
            <h2 class="mb-4 text-lg font-semibold">Request</h2>
            <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-green-100">{{ json_encode($integrationLog->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </x-dashboard-layout>
</x-app-layout>
