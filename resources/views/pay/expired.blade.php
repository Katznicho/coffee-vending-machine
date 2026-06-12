<x-guest-layout title="Payment unavailable">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">Payment unavailable</h1>
        <p class="mt-2 text-gray-600">This order is {{ $order->payment_status }}.</p>
        <p class="mt-4 text-sm text-gray-500">{{ $order->third_party_order_id }}</p>
    </div>
</x-guest-layout>
