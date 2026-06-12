<x-app-layout>
    <x-dashboard-layout title="Order {{ $order->third_party_order_id }}">
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Order details</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Reference</dt><dd class="font-mono">{{ $order->third_party_order_id }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Machine order</dt><dd class="font-mono">{{ $order->machine_order_id }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Machine</dt><dd class="font-mono">{{ $order->machine_id }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Product</dt><dd>{{ $order->product_name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Amount</dt><dd>UGX {{ number_format($order->amount) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Payment</dt><dd>{{ ucfirst($order->payment_status) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Dispense</dt><dd>{{ ucfirst($order->dispense_status) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Phone</dt><dd>{{ $order->customer_phone ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Payment URL</dt><dd><a href="{{ route('pay.show', $order->third_party_order_id) }}" class="text-brand hover:underline" target="_blank">Open</a></dd></div>
                </dl>
            </div>

            <div class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Payments</h2>
                @forelse($order->payments as $payment)
                    <div class="mb-4 rounded-lg bg-gray-50 p-4 text-sm last:mb-0">
                        <p><span class="text-gray-500">Reference:</span> <span class="font-mono">{{ $payment->reference }}</span></p>
                        <p class="mt-1"><span class="text-gray-500">Status:</span> {{ ucfirst($payment->status) }}</p>
                        <p class="mt-1"><span class="text-gray-500">Provider:</span> {{ $payment->provider ?? '—' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No payment attempts yet.</p>
                @endforelse
            </div>
        </div>
    </x-dashboard-layout>
</x-app-layout>
