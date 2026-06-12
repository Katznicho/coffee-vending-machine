<x-app-layout>
    <x-dashboard-layout title="Cellulant Settings">
        <div class="mb-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-medium">IPN URL</p>
            <p class="mt-1 font-mono break-all text-xs">{{ $ipnUrl }}</p>
        </div>

        <section class="app-card mb-8">
            <h2 class="text-lg font-semibold text-gray-900">Sandbox test</h2>
            <p class="mt-1 text-sm text-gray-600">Test your Tingg connection and optionally send a Mobile Money payment prompt.</p>

            <form method="POST" class="mt-4 space-y-4">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Phone number</label>
                        <input
                            type="text"
                            name="test_phone"
                            value="{{ old('test_phone') }}"
                            placeholder="0771234567"
                            class="w-full rounded-lg border px-3 py-2 font-mono text-sm"
                        >
                        @error('test_phone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Amount (UGX)</label>
                        <input
                            type="number"
                            name="test_amount"
                            value="{{ old('test_amount', 1000) }}"
                            min="1"
                            max="500000"
                            placeholder="1000"
                            class="w-full rounded-lg border px-3 py-2 font-mono text-sm"
                        >
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="submit"
                        formaction="{{ route('settings.cellulant.test.connectivity') }}"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50"
                    >
                        Test connection
                    </button>
                    <button
                        type="submit"
                        formaction="{{ route('settings.cellulant.test.payment') }}"
                        class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark"
                    >
                        Send test payment
                    </button>
                </div>
            </form>

            @if(session('test_results'))
                @php($testResults = session('test_results'))
                @php($allPassed = $testResults['passed'] ?? false)

                <div class="mt-5 rounded-lg border px-4 py-3 {{ $allPassed ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                    <p class="text-sm font-semibold {{ $allPassed ? 'text-green-900' : 'text-gray-900' }}">
                        {{ $testResults['headline'] ?? 'Test complete' }}
                    </p>
                    <ul class="mt-3 space-y-1.5 text-sm">
                        @foreach($testResults['steps'] ?? [] as $step)
                            <li class="flex gap-2 {{ ($step['passed'] ?? false) ? 'text-green-800' : 'text-red-800' }}">
                                <span class="font-mono">{{ ($step['passed'] ?? false) ? '✓' : '✗' }}</span>
                                <span>
                                    <span class="font-medium text-gray-900">{{ $step['name'] }}</span>
                                    — {{ $step['message'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <form method="POST" action="{{ route('settings.cellulant.update') }}" class="space-y-8">
            @csrf
            @method('PUT')

            <section class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Environment</h2>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Active environment</label>
                        <select name="environment" class="w-full rounded-lg border px-3 py-2">
                            <option value="sandbox" @selected(old('environment', $settings->environment) === 'sandbox')>Sandbox</option>
                            <option value="production" @selected(old('environment', $settings->environment) === 'production')>Production</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $settings->is_active)) class="rounded border-gray-300">
                            Cellulant integration enabled
                        </label>
                    </div>
                </div>
            </section>

            <section class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Sandbox credentials</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Base URL</label>
                        <input type="url" name="sandbox_base_url" value="{{ old('sandbox_base_url', $settings->sandbox_base_url) }}" placeholder="https://payments-instore.sandbox.tingg.africa" class="w-full rounded-lg border px-3 py-2 font-mono text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="sandbox_username" value="{{ old('sandbox_username', $settings->sandbox_username) }}" placeholder="pat_sanboxAPI_user" class="w-full rounded-lg border px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="sandbox_password" placeholder="Leave blank to keep current password" class="w-full rounded-lg border px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Counter code</label>
                        <input type="text" name="sandbox_counter_code" value="{{ old('sandbox_counter_code', $settings->sandbox_counter_code) }}" placeholder="1008" class="w-full rounded-lg border px-3 py-2 font-mono">
                    </div>
                </div>
            </section>

            <section class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Production credentials</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Base URL</label>
                        <input type="url" name="production_base_url" value="{{ old('production_base_url', $settings->production_base_url) }}" placeholder="https://payments.instore.tingg.africa" class="w-full rounded-lg border px-3 py-2 font-mono text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="production_username" value="{{ old('production_username', $settings->production_username) }}" placeholder="your_production_api_user" class="w-full rounded-lg border px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="production_password" placeholder="Leave blank to keep current password" class="w-full rounded-lg border px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Counter code</label>
                        <input type="text" name="production_counter_code" value="{{ old('production_counter_code', $settings->production_counter_code) }}" placeholder="e.g. 12150" class="w-full rounded-lg border px-3 py-2 font-mono">
                    </div>
                </div>
            </section>

            <section class="app-card">
                <h2 class="mb-4 text-lg font-semibold">Payment options</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Initiate payment path</label>
                        <input type="text" name="initiate_payment_path" value="{{ old('initiate_payment_path', $settings->initiate_payment_path) }}" placeholder="/initiateMerchantPayment" class="w-full rounded-lg border px-3 py-2 font-mono text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">MTN payer client code</label>
                        <input type="text" name="default_payer_client_code" value="{{ old('default_payer_client_code', $settings->default_payer_client_code) }}" placeholder="MTNUG" class="w-full rounded-lg border px-3 py-2 font-mono">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Airtel payer client code</label>
                        <input type="text" name="airtel_payer_client_code" value="{{ old('airtel_payer_client_code', $settings->airtel_payer_client_code) }}" placeholder="AIRTELUG" class="w-full rounded-lg border px-3 py-2 font-mono">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Country code</label>
                        <input type="text" name="country_code" value="{{ old('country_code', $settings->country_code) }}" placeholder="UGA" maxlength="3" class="w-full rounded-lg border px-3 py-2 font-mono uppercase">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Currency code</label>
                        <input type="text" name="currency_code" value="{{ old('currency_code', $settings->currency_code) }}" placeholder="UGX" maxlength="3" class="w-full rounded-lg border px-3 py-2 font-mono uppercase">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="auto_detect_payer" value="1" @checked(old('auto_detect_payer', $settings->auto_detect_payer)) class="rounded border-gray-300">
                            Auto-detect MTN/Airtel from phone number
                        </label>
                    </div>
                </div>
            </section>

            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                Save settings
            </button>
        </form>
    </x-dashboard-layout>
</x-app-layout>
