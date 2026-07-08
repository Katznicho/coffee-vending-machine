<x-app-layout>
    <x-dashboard-layout title="Create order">
        <form method="POST" action="{{ route('orders.store') }}" class="app-card max-w-xl space-y-5">
            @csrf

            <p class="text-sm text-gray-600">
                Creating an order initiates a Cellulant Mobile Money prompt on the customer's phone, just like the vending machine does.
            </p>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Machine</label>
                <select name="machine_id" required class="w-full rounded-lg border px-3 py-2">
                    <option value="">Select a machine</option>
                    @foreach($machines as $machine)
                        <option value="{{ $machine->machine_id }}" @selected(old('machine_id') === $machine->machine_id)>
                            {{ $machine->name }} ({{ $machine->machine_id }})
                        </option>
                    @endforeach
                </select>
                @error('machine_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Product</label>
                <input type="text" name="product_name" value="{{ old('product_name') }}" required placeholder="Cappuccino" class="w-full rounded-lg border px-3 py-2">
                @error('product_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Amount (UGX)</label>
                <input type="number" name="amount" value="{{ old('amount') }}" required min="1" placeholder="5000" class="w-full rounded-lg border px-3 py-2">
                @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Customer phone</label>
                <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" required placeholder="0759983853" class="w-full rounded-lg border px-3 py-2">
                @error('customer_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                    Create &amp; send prompt
                </button>
                <a href="{{ route('orders.index') }}" class="rounded-lg border px-4 py-2 text-sm">Cancel</a>
            </div>
        </form>
    </x-dashboard-layout>
</x-app-layout>
