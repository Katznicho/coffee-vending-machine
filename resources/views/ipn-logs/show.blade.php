<x-app-layout>
    <x-dashboard-layout title="IPN log #{{ $ipnLog->id }}">
        <div class="mb-4">
            <a href="{{ route('ipn-logs.index') }}" class="text-sm text-brand hover:underline">&larr; Back to IPN logs</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Summary</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Received</dt><dd>{{ $ipnLog->created_at->format('M j, Y H:i:s') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Payment status</dt><dd class="font-mono">{{ $ipnLog->paymentStatusCode() ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Description</dt><dd>{{ $ipnLog->paymentStatusDescription() ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Merchant txn</dt><dd class="font-mono">{{ $ipnLog->merchant_transaction_id ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Reference</dt><dd class="font-mono">{{ $ipnLog->reference ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Phone</dt><dd>{{ $ipnLog->msisdn ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Amount</dt><dd>{{ $ipnLog->amount !== null ? 'UGX '.number_format($ipnLog->amount) : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Order matched</dt><dd>{{ $ipnLog->order_matched ? 'Yes' : 'No' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Order</dt>
                        <dd>
                            @if($ipnLog->order)
                                <a href="{{ route('orders.show', $ipnLog->order) }}" class="font-mono text-brand hover:underline">{{ $ipnLog->order->third_party_order_id }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">IP address</dt><dd class="font-mono">{{ $ipnLog->ip_address ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Response sent to Cellulant</h2>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-green-100">{{ json_encode($ipnLog->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>

        <div class="app-card mt-6">
            <h2 class="mb-4 text-lg font-semibold">Incoming payload</h2>
            <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-green-100">{{ json_encode($ipnLog->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </x-dashboard-layout>
</x-app-layout>
