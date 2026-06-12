<x-app-layout title="API Keys Management - Emicon Station Manager">
    <x-dashboard-layout header="API Keys Management">
        <div class="max-w-7xl mx-auto space-y-6">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-8 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">API Keys Management</h1>
                        <p class="mt-2 text-blue-100">Manage secure API access for your branch</p>
                    </div>
                    <div class="bg-white/20 rounded-lg p-3 backdrop-blur-sm">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1-1 4.243-4.243A6 6 0 1118 8zm-6 4a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/10 rounded-lg p-4 backdrop-blur-sm">
                        <div class="text-2xl font-bold">{{ auth()->user()->branch?->name ?? 'Your Branch' }}</div>
                        <div class="text-blue-100 text-sm">Current Branch</div>
                    </div>
                    <div class="bg-white/10 rounded-lg p-4 backdrop-blur-sm">
                        <div class="text-2xl font-bold">{{ $credentials && $credentials['username'] ? 'Active' : 'Not Set' }}</div>
                        <div class="text-blue-100 text-sm">API Status</div>
                    </div>
                    <div class="bg-white/10 rounded-lg p-4 backdrop-blur-sm">
                        <div class="text-2xl font-bold">{{ $credentials && $credentials['username'] ? 'Secure' : 'Setup Needed' }}</div>
                        <div class="text-blue-100 text-sm">Security Status</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="bg-blue-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 012 6m-8-6a2 2 0 00-2-2m-4 0a2 2 0 00-2 2m8 6a2 2 0 002 2m-8 0a2 2 0 00-2 2"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="font-semibold text-gray-900">Generate Keys</h3>
                            <p class="text-sm text-gray-600">Create new API credentials</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="font-semibold text-gray-900">Test Connection</h3>
                            <p class="text-sm text-gray-600">Verify API connectivity</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="bg-purple-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="font-semibold text-gray-900">Security</h3>
                            <p class="text-sm text-gray-600">Manage access controls</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Credentials Display -->
            @if ($credentials && $credentials['username'])
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900">API Credentials</h2>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ ($credentials['existing'] ?? false) ? 'Existing' : 'New' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- Username -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div>
                                <div class="text-sm font-medium text-gray-900">Username</div>
                                <div class="text-sm text-gray-600 mt-1">API username for authentication</div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <code class="text-sm font-mono text-gray-800 bg-white px-3 py-2 rounded border border-gray-300">
                                    {{ $credentials['username'] }}
                                </code>
                                <button onclick="copyCredentials('{{ $credentials['username'] }}')" class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div>
                                <div class="text-sm font-medium text-gray-900">Password</div>
                                <div class="text-sm text-gray-600 mt-1">API password for authentication</div>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if (($credentials['existing'] ?? false))
                                    <div class="text-sm text-gray-500 italic">Hidden (for security)</div>
                                    <button onclick="regenerateKey({{ $business->branches->first()->id }})" class="p-2 text-orange-400 hover:text-orange-600 transition-colors" title="Regenerate to see password">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                @else
                                    <code class="text-sm font-mono text-gray-800 bg-white px-3 py-2 rounded border border-gray-300">
                                        {{ $credentials['password'] }}
                                    </code>
                                    <button onclick="copyCredentials('{{ $credentials['password'] }}')" class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if (!($credentials['existing'] ?? false))
                            <!-- Combined Credentials -->
                            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <div>
                                    <div class="text-sm font-medium text-blue-900">Combined Credentials</div>
                                    <div class="text-sm text-blue-600 mt-1">Username:Password format</div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <code class="text-sm font-mono text-blue-800 bg-white px-3 py-2 rounded border border-blue-300">
                                        {{ $credentials['username'] }}:{{ $credentials['password'] }}
                                    </code>
                                    <button onclick="copyCredentials('{{ $credentials['username'] }}:{{ $credentials['password'] }}')" class="p-2 text-blue-400 hover:text-blue-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Base64 -->
                            <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg border border-purple-200">
                                <div>
                                    <div class="text-sm font-medium text-purple-900">Base64 Authorization Header</div>
                                    <div class="text-sm text-purple-600 mt-1">Ready to use in HTTP headers</div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <code class="text-sm font-mono text-purple-800 bg-white px-3 py-2 rounded border border-purple-300 max-w-xs truncate">
                                        Basic {{ base64_encode($credentials['username'] . ':' . $credentials['password']) }}
                                    </code>
                                    <button onclick="copyBase64('{{ base64_encode($credentials['username'] . ':' . $credentials['password']) }}')" class="p-2 text-purple-400 hover:text-purple-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Usage Examples -->
                            <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                                <div class="text-sm font-medium text-gray-900 mb-3">Usage Examples</div>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-medium text-gray-700">cURL</div>
                                            <code class="text-xs text-gray-600 bg-white px-2 py-1 rounded border border-gray-300 block mt-1">
                                                curl -H "Authorization: Basic {{ base64_encode($credentials['username'] . ':' . $credentials['password']) }}"
                                            </code>
                                        </div>
                                        <button onclick="copyCurl('{{ base64_encode($credentials['username'] . ':' . $credentials['password']) }}')" class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-medium text-gray-700">JavaScript</div>
                                            <code class="text-xs text-gray-600 bg-white px-2 py-1 rounded border border-gray-300 block mt-1">
                                                headers: {'Authorization': 'Basic {{ base64_encode($credentials['username'] . ':' . $credentials['password']) }}'}
                                            </code>
                                        </div>
                                        <button onclick="copyCredentials('headers: {\"Authorization\": \"Basic {{ base64_encode($credentials['username'] . ':' . $credentials['password']) }}\"}')" class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- For existing credentials, show a message -->
                            <div class="bg-yellow-50 rounded-lg border border-yellow-200 p-4">
                                <div class="text-sm text-yellow-800">
                                    <div class="font-semibold mb-2">🔒 Existing Credentials</div>
                                    <div class="text-xs text-yellow-700">
                                        For security reasons, the existing password is hidden. To see the complete credentials including the password and Base64 authorization header, click "Regenerate" to create new credentials.
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- No Credentials Section -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900">API Credentials</h2>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Not Set
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No API Credentials Set</h3>
                            <p class="text-sm text-gray-600 mb-6">
                                Generate API credentials to enable secure access to your branch's forecourt systems and data.
                            </p>
                            
                            <button onclick="generateKey({{ $business->branches->first()->id }})" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Generate API Credentials
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Test Connection Section -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">API Connection Test</h2>
                    <p class="mt-1 text-sm text-gray-600">Verify your API endpoints are working correctly</p>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('settings.api-keys.test-connection') }}" class="space-y-4">
                        @csrf
                        @if(!auth()->user()->branch_id)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Branch</label>
                                <select name="branch_uuid" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Use first available branch</option>
                                    @foreach(($business?->branches ?? collect()) as $branch)
                                        <option value="{{ $branch->uuid }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Test will run for your assigned branch: {{ auth()->user()->branch->name ?? 'Unknown' }}
                                </div>
                            </div>
                        @endif

                        <button type="submit" class="w-full md:w-auto rounded-lg bg-blue-600 px-6 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Run Connection Test
                        </button>
                    </form>
                </div>
            </div>

            <!-- API Usage Stats -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">API Usage Statistics</h2>
                    <p class="mt-1 text-sm text-gray-600">Monitor your branch API activity and performance</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">0</div>
                            <div class="text-sm text-gray-600">Total Requests</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">0</div>
                            <div class="text-sm text-gray-600">Today</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">0ms</div>
                            <div class="text-sm text-gray-600">Avg Response</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">100%</div>
                            <div class="text-sm text-gray-600">Uptime</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-dashboard-layout>
</x-app-layout>

<!-- Hidden forms for key generation -->
<form id="regenerateForm" method="POST" action="{{ route('settings.api-keys.regenerate') }}" style="display: none;">
    @csrf
    <input type="hidden" name="branch_id" id="regenerateBranchId">
    <input type="hidden" name="current_password" id="regeneratePassword">
</form>

<form id="generateForm" method="POST" action="{{ route('settings.api-keys.regenerate') }}" style="display: none;">
    @csrf
    <input type="hidden" name="branch_id" id="generateBranchId">
    <input type="hidden" name="current_password" id="generatePassword">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: @json(session('success')),
            timer: 3000,
            showConfirmButton: false,
            position: 'top-end',
            toast: true
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: @json(session('error')),
            timer: 3000,
            showConfirmButton: false,
            position: 'top-end',
            toast: true
        });
    @endif

    function copyCredentials(credentials) {
        navigator.clipboard.writeText(credentials).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Credentials copied to clipboard',
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        }).catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Copy Failed',
                text: 'Could not copy credentials',
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        });
    }

    function copyBase64(base64String) {
        const authorizationHeader = `Basic ${base64String}`;
        navigator.clipboard.writeText(authorizationHeader).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Base64 Copied!',
                text: 'Authorization header copied to clipboard',
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        }).catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Copy Failed',
                text: 'Could not copy Base64 header',
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        });
    }

    function copyCurl(base64String) {
        const curlCommand = `curl -X POST "http://127.0.0.1:8000/api/health" -H "Authorization: Basic ${base64String}"`;
        navigator.clipboard.writeText(curlCommand).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Curl Command Copied!',
                text: 'Ready-to-use curl command copied',
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        }).catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Copy Failed',
                text: 'Could not copy curl command',
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        });
    }

    function regenerateKey(branchId) {
        Swal.fire({
            title: 'Regenerate API Key',
            text: 'This will invalidate the current API key. Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, regenerate',
            input: 'password',
            inputLabel: 'Enter your password to confirm',
            inputPlaceholder: 'Enter your password',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to enter your password!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('regenerateBranchId').value = branchId;
                document.getElementById('regeneratePassword').value = result.value;
                document.getElementById('regenerateForm').submit();
            }
        });
    }

    function generateKey(branchId) {
        Swal.fire({
            title: 'Generate API Key',
            text: 'Create new API credentials for this branch',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Generate Key',
            input: 'password',
            inputLabel: 'Enter your password to confirm',
            inputPlaceholder: 'Enter your password',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to enter your password!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('generateBranchId').value = branchId;
                document.getElementById('generatePassword').value = result.value;
                document.getElementById('generateForm').submit();
            }
        });
    }

    function refreshApiKeys() {
        window.location.reload();
    }

    // Add some interactivity
    document.addEventListener('DOMContentLoaded', function() {
        // Animate stats on load
        const stats = document.querySelectorAll('.text-2xl');
        stats.forEach((stat, index) => {
            setTimeout(() => {
                stat.style.opacity = '0';
                stat.style.transform = 'translateY(10px)';
                stat.style.transition = 'all 0.3s ease';
                setTimeout(() => {
                    stat.style.opacity = '1';
                    stat.style.transform = 'translateY(0)';
                }, 50);
            }, index * 100);
        });
    });
</script>

