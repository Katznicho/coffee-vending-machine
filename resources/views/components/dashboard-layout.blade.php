<div class="flex min-h-screen bg-gray-50">
    <aside class="w-64 flex-shrink-0 bg-brand text-white">
        <div class="flex h-full flex-col p-6">
            <div class="mb-8">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white p-1 shadow-sm">
                    <img src="{{ asset('logo.jpg') }}" alt="La Patisserie Express" class="h-full w-full rounded-full object-cover">
                </div>
                <p class="mt-3 text-sm text-white/90">{{ config('app.name') }}</p>
            </div>

            <nav class="flex-1 space-y-2">
                <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-white/20' : 'hover:bg-white/10' }}">
                    Dashboard
                </a>
                <a href="{{ route('machines.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('machines.*') ? 'bg-white/20' : 'hover:bg-white/10' }}">
                    Machines
                </a>
                <a href="{{ route('orders.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('orders.*') ? 'bg-white/20' : 'hover:bg-white/10' }}">
                    Orders
                </a>
                <a href="{{ route('integration.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('integration.*') ? 'bg-white/20' : 'hover:bg-white/10' }}">
                    Integration
                </a>
                <a href="{{ route('settings.cellulant.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('settings.cellulant.*') ? 'bg-white/20' : 'hover:bg-white/10' }}">
                    Cellulant
                </a>
                <a href="{{ route('ipn-logs.index') }}" class="block rounded-lg py-2 pl-6 pr-3 text-sm font-medium {{ request()->routeIs('ipn-logs.*') ? 'bg-white/20' : 'hover:bg-white/10' }}">
                    IPN logs
                </a>
                <a href="{{ route('integration-logs.index') }}" class="block rounded-lg py-2 pl-6 pr-3 text-sm font-medium {{ request()->routeIs('integration-logs.*') ? 'bg-white/20' : 'hover:bg-white/10' }}">
                    API logs
                </a>
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="mt-8">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-white/30 px-3 py-2 text-sm hover:bg-white/10">
                    Log out
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto">
        <header class="bg-white px-8 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">{{ $title ?? 'Dashboard' }}</h1>
                <p class="text-sm text-gray-500">{{ Auth::user()->name }}</p>
            </div>
        </header>

        <div class="p-8">
            @if(session('success'))
                <div class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>
