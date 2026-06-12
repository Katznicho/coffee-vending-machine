<x-guest-layout title="Pay {{ $order->product_name }}">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ $order->product_name }}</h1>
        <p class="mt-2 text-3xl font-semibold text-brand">UGX {{ number_format($order->amount) }}</p>
        <p class="mt-2 text-sm text-gray-500">Order {{ $order->third_party_order_id }}</p>
    </div>

    @if(session('status'))
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('pay.submit', $order->third_party_order_id) }}" class="mt-8">
        @csrf
        <label for="phone_number" class="mb-2 block text-sm font-medium text-gray-700">Mobile Money number</label>
        <input
            id="phone_number"
            type="tel"
            name="phone_number"
            value="{{ old('phone_number', $order->customer_phone) }}"
            placeholder="256759983853 or 0759983853"
            required
            class="mb-2 w-full rounded-lg border-2 border-gray-200 px-3 py-2 focus:border-brand focus:outline-none"
        >
        <p class="mb-4 text-xs text-gray-500">MTN or Airtel Uganda. You will receive a prompt on your phone.</p>
        @error('phone_number')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <button type="submit" class="w-full rounded-lg bg-brand py-3 text-base font-semibold text-white hover:bg-brand-dark">
            Pay now
        </button>
    </form>
</x-guest-layout>
