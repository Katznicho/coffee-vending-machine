<x-app-layout title="Products - Emicon Station Manager">
    <x-dashboard-layout header="Products">
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-500">Products sold at your station branches.</p>
            <a href="{{ route('products.create') }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Add product</a>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Product</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Branch</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">SKU</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Unit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $product->branch?->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $product->sku ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $product->unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    </x-dashboard-layout>
</x-app-layout>
