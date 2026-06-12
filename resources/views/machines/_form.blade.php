<form method="POST" action="{{ $machine->exists ? route('machines.update', $machine) : route('machines.store') }}" class="app-card max-w-xl space-y-5">
    @csrf
    @if($machine->exists)
        @method('PUT')
    @endif

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Machine ID</label>
        <input type="text" name="machine_id" value="{{ old('machine_id', $machine->machine_id) }}" required placeholder="00000022481" class="w-full rounded-lg border px-3 py-2 font-mono">
        @error('machine_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" value="{{ old('name', $machine->name) }}" required placeholder="Ranchers Finest Coffee Machine" class="w-full rounded-lg border px-3 py-2">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Location</label>
        <input type="text" name="location" value="{{ old('location', $machine->location) }}" placeholder="Kampala" class="w-full rounded-lg border px-3 py-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Secret key (appkey)</label>
        <input type="text" name="secret_key" value="{{ old('secret_key', $machine->secret_key) }}" required placeholder="your-machine-secret-key" class="w-full rounded-lg border px-3 py-2 font-mono text-sm">
        @error('secret_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
        <select name="status" class="w-full rounded-lg border px-3 py-2">
            <option value="active" @selected(old('status', $machine->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $machine->status) === 'inactive')>Inactive</option>
        </select>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
            Save
        </button>
        <a href="{{ route('machines.index') }}" class="rounded-lg border px-4 py-2 text-sm">Cancel</a>
    </div>
</form>
