<x-guest-layout title="Forgot Password">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Forgot Password?</h2>
        <p class="text-sm text-gray-600">Enter your email address and we'll send you a link to reset your password.</p>
    </div>

    @if(session('status'))
        <div class="mb-6 px-4 py-3 bg-accent-50 border-l-4 border-accent-400 rounded-lg">
            <p class="text-sm font-medium text-primary-800">{{ session('status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
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
                class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-base focus:outline-none focus:border-primary-700 transition-colors"
            >
            @error('email')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
        
        <button type="submit" class="w-full rounded-lg bg-brand py-3 text-base font-semibold text-white transition-all hover:bg-brand-dark">
            Send Password Reset Link
        </button>
    </form>
    
    <div class="text-center mt-6 text-gray-600 text-sm">
        Remember your password? <a href="{{ route('login') }}" class="font-medium text-brand hover:underline">Sign in here</a>
    </div>
</x-guest-layout>

