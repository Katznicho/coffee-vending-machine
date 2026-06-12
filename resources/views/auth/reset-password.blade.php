<x-guest-layout title="Reset Password">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Reset Your Password</h2>
        <p class="text-sm text-gray-600">Enter your new password below.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        
        <div class="mb-6">
            <label for="email_display" class="block mb-2 text-gray-700 font-medium">Email Address</label>
            <input 
                id="email_display" 
                type="email" 
                value="{{ $email }}" 
                disabled
                class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-base bg-gray-100 text-gray-500"
            >
        </div>
        
        <div class="mb-6">
            <label for="password" class="block mb-2 text-gray-700 font-medium">New Password</label>
            <div class="relative">
                <input 
                    id="password" 
                    type="password" 
                    name="password" 
                    required
                    placeholder="Enter your new password"
                    oninput="checkPasswordStrength(this.value)"
                    class="w-full px-3 py-2 pr-10 border-2 border-gray-200 rounded-lg text-base focus:outline-none focus:border-primary-700 transition-colors"
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
            <!-- Password Requirements -->
            <div id="password-requirements" class="mt-2 space-y-1">
                <div class="text-xs font-semibold text-gray-600 mb-2">Password Requirements:</div>
                <div class="space-y-1">
                    <div id="req-length" class="text-xs flex items-center">
                        <span class="req-icon mr-2">❌</span>
                        <span>At least 8 characters</span>
                    </div>
                    <div id="req-lowercase" class="text-xs flex items-center">
                        <span class="req-icon mr-2">❌</span>
                        <span>One lowercase letter (a-z)</span>
                    </div>
                    <div id="req-uppercase" class="text-xs flex items-center">
                        <span class="req-icon mr-2">❌</span>
                        <span>One uppercase letter (A-Z)</span>
                    </div>
                    <div id="req-number" class="text-xs flex items-center">
                        <span class="req-icon mr-2">❌</span>
                        <span>One number (0-9)</span>
                    </div>
                    <div id="req-special" class="text-xs flex items-center">
                        <span class="req-icon mr-2">❌</span>
                        <span>One special character (@$!%*#?&)</span>
                    </div>
                </div>
            </div>
            <!-- Password Strength Indicator -->
            <div id="password-strength" class="mt-2 hidden">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-semibold text-gray-600">Strength:</span>
                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div id="strength-bar" class="h-full transition-all duration-300 rounded-full" style="width: 0%;"></div>
                    </div>
                    <span id="strength-text" class="text-xs font-semibold"></span>
                </div>
            </div>
            @error('password')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="mb-6 -mt-2">
            <label for="password_confirmation" class="block mb-2 text-gray-700 font-medium">Confirm New Password</label>
            <div class="relative">
                <input 
                    id="password_confirmation" 
                    type="password" 
                    name="password_confirmation" 
                    required
                    placeholder="Confirm your new password"
                    class="w-full px-3 py-2 pr-10 border-2 border-gray-200 rounded-lg text-base focus:outline-none focus:border-primary-700 transition-colors"
                >
                <button 
                    type="button" 
                    onclick="togglePassword('password_confirmation')"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 cursor-pointer"
                >
                    <svg id="eye-password_confirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="eye-off-password_confirmation" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <button type="submit" class="w-full rounded-lg bg-brand py-3 text-base font-semibold text-white transition-all hover:bg-brand-dark">
            Reset Password
        </button>
    </form>
    
    <div class="text-center mt-6 text-gray-600 text-sm">
        Remember your password? <a href="{{ route('login') }}" class="font-medium text-brand hover:underline">Sign in here</a>
    </div>
    
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

        function checkPasswordStrength(password) {
            // Check requirements
            const hasLength = password.length >= 8;
            const hasLowercase = /[a-z]/.test(password);
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[@$!%*#?&]/.test(password);

            // Update requirement indicators
            updateRequirement('req-length', hasLength);
            updateRequirement('req-lowercase', hasLowercase);
            updateRequirement('req-uppercase', hasUppercase);
            updateRequirement('req-number', hasNumber);
            updateRequirement('req-special', hasSpecial);

            // Calculate strength score (0-100)
            let strength = 0;
            if (hasLength) strength += 20;
            if (hasLowercase) strength += 20;
            if (hasUppercase) strength += 20;
            if (hasNumber) strength += 20;
            if (hasSpecial) strength += 20;

            // Check for common weak passwords
            const weakPasswords = ['12345678', 'password', 'qwerty123', 'abc12345', 'password1', 'welcome1', 'admin123'];
            if (weakPasswords.includes(password.toLowerCase())) {
                strength = 0;
            }

            // Update strength indicator
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            const strengthContainer = document.getElementById('password-strength');

            if (password.length > 0) {
                strengthContainer.classList.remove('hidden');
                
                strengthBar.style.width = strength + '%';
                
                if (strength < 40) {
                    strengthBar.style.backgroundColor = '#ef4444'; // red
                    strengthText.textContent = 'Weak';
                    strengthText.className = 'text-xs font-semibold text-red-600';
                } else if (strength < 80) {
                    strengthBar.style.backgroundColor = '#2563eb'; // primary blue
                    strengthText.textContent = 'Fair';
                    strengthText.className = 'text-xs font-semibold text-primary-600';
                } else if (strength < 100) {
                    strengthBar.style.backgroundColor = '#3b82f6'; // blue
                    strengthText.textContent = 'Good';
                    strengthText.className = 'text-xs font-semibold text-primary-600';
                } else {
                    strengthBar.style.backgroundColor = '#10b981'; // green
                    strengthText.textContent = 'Strong';
                    strengthText.className = 'text-xs font-semibold text-primary-600';
                }
            } else {
                strengthContainer.classList.add('hidden');
            }
        }

        function updateRequirement(elementId, isValid) {
            const element = document.getElementById(elementId);
            const icon = element.querySelector('.req-icon');
            const text = element.querySelector('span:last-child');
            
            if (isValid) {
                icon.textContent = '✅';
                text.style.color = '#10b981'; // green
            } else {
                icon.textContent = '❌';
                text.style.color = '#6b7280'; // gray
            }
        }
    </script>
    @endpush
</x-guest-layout>

