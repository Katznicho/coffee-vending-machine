<form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="app-card max-w-xl space-y-5">
    @csrf
    @if($user->exists)
        @method('PUT')
    @endif

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required placeholder="e.g. Jane Doe" class="w-full rounded-lg border px-3 py-2">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required placeholder="e.g. jane@example.com" class="w-full rounded-lg border px-3 py-2">
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">
            Password
            @if($user->exists)<span class="font-normal text-gray-500">(leave blank to keep current)</span>@endif
        </label>
        <input type="password" name="password" @if(! $user->exists) required @endif placeholder="{{ $user->exists ? 'Leave blank to keep current password' : 'At least 8 characters' }}" class="w-full rounded-lg border px-3 py-2">
        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Confirm password</label>
        <input type="password" name="password_confirmation" @if(! $user->exists) required @endif placeholder="Re-enter the password" class="w-full rounded-lg border px-3 py-2">
    </div>

    @if(! ($user->exists && $user->id === auth()->id()))
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="is_admin" value="0">
            <input type="checkbox" name="is_admin" value="1" class="rounded border-gray-300" @checked(old('is_admin', $user->is_admin ?? true))>
            Administrator (full access)
        </label>
    @endif

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
            Save
        </button>
        <a href="{{ route('users.index') }}" class="rounded-lg border px-4 py-2 text-sm">Cancel</a>
    </div>
</form>
