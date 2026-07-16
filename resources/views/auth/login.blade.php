<x-guest-layout title="Login">
    @if(session('status'))
        <div class="mb-6 px-4 py-3 bg-accent-50 border-l-4 border-accent-400 rounded-lg">
            <p class="text-sm font-medium text-primary-800">{{ session('status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        
        <div class="mb-6">
            <label for="email" class="block mb-2 text-gray-700 font-medium">Email Address</label>
            <input 
                id="email" 
                type="email" 
                name="email" 
                value="{{ old('email') }}" 
                required 
                autofocus
                placeholder="Enter your email address"
                class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 text-base transition-colors focus:border-brand focus:outline-none"
            >
            @error('email')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="mb-6">
            <label for="password" class="block mb-2 text-gray-700 font-medium">Password</label>
            <div class="relative">
                <input 
                    id="password" 
                    type="password" 
                    name="password" 
                    required
                    placeholder="Enter your password"
                    class="w-full rounded-lg border-2 border-gray-200 px-3 py-2 pr-10 text-base transition-colors focus:border-brand focus:outline-none"
                >
                <button 
                    type="button" 
                    onclick="togglePassword('password')"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 cursor-pointer"
                >
                    <svg id="eye-password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="eye-off-password" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="mb-6 flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand hover:underline">Forgot password?</a>
        </div>
        
        <button
            type="submit"
            class="w-full rounded-lg bg-brand py-3 text-base font-semibold text-white shadow-sm transition hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
        >
            Sign in
        </button>
    </form>

    @push('scripts')
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById('eye-' + fieldId);
            const eyeOff = document.getElementById('eye-off-' + fieldId);
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.classList.add('hidden');
                eyeOff.classList.remove('hidden');
            } else {
                field.type = 'password';
                eye.classList.remove('hidden');
                eyeOff.classList.add('hidden');
            }
        }
    </script>
    @endpush
</x-guest-layout>

