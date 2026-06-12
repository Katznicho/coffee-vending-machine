<footer class="bg-gray-900 text-white py-8 sm:py-12 mt-auto w-full relative z-10" style="background-color: #111827 !important; min-height: 200px;">
    <div class="container mx-auto max-w-7xl px-4 sm:px-6 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <div>
                <h3 class="font-semibold text-lg mb-4 text-white">Product</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
                    <li><a href="{{ route('docs.index') }}" class="text-gray-400 hover:text-white transition-colors">Documentation</a></li>
                    <li><a href="{{ route('pricing') }}" class="text-gray-400 hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="{{ route('features') }}" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-lg mb-4 text-white">Resources</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('docs.index') }}#getting-started" class="text-gray-400 hover:text-white transition-colors">Getting Started</a></li>
                    <li><a href="{{ route('docs.index') }}#send-sms" class="text-gray-400 hover:text-white transition-colors">API Reference</a></li>
                    <li><a href="{{ route('docs.index') }}#error-codes" class="text-gray-400 hover:text-white transition-colors">Error Codes</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Support</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-lg mb-4 text-white">Company</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="mailto:info@sms.wearemarz.com" class="text-gray-400 hover:text-white transition-colors">info@sms.wearemarz.com</a></li>
                    <li><a href="tel:+256759983853" class="text-gray-400 hover:text-white transition-colors">+256 759 983 853</a></li>
                    <li><a href="tel:+256781230949" class="text-gray-400 hover:text-white transition-colors">+256 781 230 949</a></li>
                    <li class="text-gray-400 mt-3">
                        <div class="text-xs leading-relaxed">
                            Fraine Ntinda, Last Floor<br>
                            Office 27<br>
                            Kampala, Uganda
                        </div>
                    </li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-lg mb-4 text-white">Connect</h3>
                <ul class="space-y-2 text-sm">
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white transition-colors">Dashboard</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition-colors">Sign In</a></li>
                        <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition-colors">Sign In</a></li>
                    @endauth
                </ul>
            </div>
        </div>
        <div class="pt-8 border-t border-gray-700 text-center">
            <p class="text-gray-300 text-sm mb-2">&copy; {{ date('Y') }} Emicon Station Manager. All rights reserved.</p>
            <p class="text-gray-400 text-xs">Emicon Station Manager</p>
        </div>
    </div>
</footer>

