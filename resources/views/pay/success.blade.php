<x-guest-layout title="Payment successful">
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl text-green-700">✓</div>
        <h1 class="text-2xl font-bold text-gray-900">Payment received</h1>
        <p class="mt-2 text-gray-600">Your {{ $order->product_name }} is being prepared.</p>
        <p class="mt-4 text-sm text-gray-500">UGX {{ number_format($order->amount) }} · {{ $order->third_party_order_id }}</p>
    </div>
</x-guest-layout>
