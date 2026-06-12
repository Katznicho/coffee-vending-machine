<x-app-layout title="Add Product - Emicon Station Manager">
    <x-dashboard-layout header="Add product">
        <form method="POST" action="{{ route('products.store') }}" class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Name *</label>
                    <input name="name" value="{{ old('name') }}" required placeholder="Product name" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Branch *</label>
                    <select name="branch_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Select branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">SKU</label>
                    <input name="sku" value="{{ old('sku') }}" placeholder="SKU-001" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Unit</label>
                    <input name="unit" value="{{ old('unit', 'L') }}" placeholder="L" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('products.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</a>
                <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Create product</button>
            </div>
        </form>
    </x-dashboard-layout>
</x-app-layout>
