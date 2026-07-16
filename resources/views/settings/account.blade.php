<x-app-layout>
    <x-dashboard-layout title="Account settings">
        <div class="grid gap-6 lg:grid-cols-2">
            <form method="POST" action="{{ route('settings.account.profile') }}" class="app-card space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Profile</h2>
                    <p class="mt-1 text-sm text-gray-600">Update your name and email address.</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border px-3 py-2">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border px-3 py-2">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                    Save profile
                </button>
            </form>

            <form method="POST" action="{{ route('settings.account.password') }}" class="app-card space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Password</h2>
                    <p class="mt-1 text-sm text-gray-600">Use a strong password of at least 8 characters.</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Current password</label>
                    <input type="password" name="current_password" required class="w-full rounded-lg border px-3 py-2">
                    @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">New password</label>
                    <input type="password" name="password" required class="w-full rounded-lg border px-3 py-2">
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Confirm new password</label>
                    <input type="password" name="password_confirmation" required class="w-full rounded-lg border px-3 py-2">
                </div>

                <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                    Change password
                </button>
            </form>
        </div>
    </x-dashboard-layout>
</x-app-layout>
