@php
    $endpoints = [
        ['field' => 'WEBURL_A', 'label' => 'Create order', 'url' => $apiBaseUrl.'/create-order'],
        ['field' => 'WEBURL_C', 'label' => 'Payment status', 'url' => $apiBaseUrl.'/payment-status'],
        ['field' => 'WEBURL_B', 'label' => 'Dispense result', 'url' => $apiBaseUrl.'/dispense-result'],
    ];
@endphp

<x-app-layout>
    <x-dashboard-layout title="Machine Integration">
        <div class="app-panel">
            <div class="px-6 py-4">
                <p class="text-sm text-gray-600">
                    Configure these URLs on the vending machine to complete the Mobile Money payment flow.
                </p>
            </div>

            <div class="space-y-6 px-6 pb-6 text-sm">
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="font-medium text-gray-900">Payment flow</p>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-gray-700">
                        <li>Customer selects a product and enters a phone number on the machine.</li>
                        <li>Machine calls <strong>Create order</strong> — middleware initiates Cellulant and returns <code class="rounded bg-white px-1">PENDING</code>. If the same order is submitted again, middleware checks Cellulant status before responding.</li>
                        <li>Customer approves the Mobile Money prompt on their phone.</li>
                        <li>Cellulant notifies middleware via IPN (no machine action required).</li>
                        <li>Machine polls <strong>Payment status</strong> until <code class="rounded bg-white px-1">paid: true</code>. If IPN is delayed, middleware falls back to Cellulant payment status APIs.</li>
                        <li>Machine dispenses the product, then calls <strong>Dispense result</strong>.</li>
                    </ol>
                    <div class="mt-4">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">IPN URL</p>
                        <x-copy-url :url="$ipnUrl" />
                        <p class="mt-2 text-xs text-gray-500">Register this URL in the Tingg portal.</p>
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Machine endpoints</p>
                    <div class="space-y-3">
                        @foreach($endpoints as $endpoint)
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-brand/10 px-2 py-0.5 font-mono text-xs font-semibold text-brand">{{ $endpoint['field'] }}</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $endpoint['label'] }}</span>
                                    <span class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-600">POST</span>
                                </div>
                                <x-copy-url :url="$endpoint['url']" compact />
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    @foreach([
                        [
                            'title' => 'Create order',
                            'url' => $apiBaseUrl.'/create-order',
                            'request' => '{
  "orderId": "ORD123",
  "machineId": "00000022481",
  "product": "Cappuccino",
  "amount": 5000,
  "phoneNumber": "256759983853",
  "timestamp": "20260610143000",
  "randstr": "abcd1234",
  "sign": "sha1 signature"
}',
                            'response' => '{
  "status": "PENDING",
  "transactionId": "ORD123",
  "paid": false
}',
                            'extra' => 'Duplicate requests for the same orderId check Cellulant status and may return PAID if payment completed.',
                        ],
                        [
                            'title' => 'Payment status',
                            'url' => $apiBaseUrl.'/payment-status',
                            'request' => '{
  "transactionId": "ORD123"
}',
                            'response' => '{
  "paid": true,
  "transactionId": "ORD123",
  "status": "PAID"
}',
                            'extra' => 'Poll every few seconds until paid.',
                        ],
                        [
                            'title' => 'Dispense result',
                            'url' => $apiBaseUrl.'/dispense-result',
                            'request' => '{
  "transactionId": "ORD123",
  "status": "SUCCESS"
}',
                            'response' => '{
  "status": "RECEIVED",
  "transactionId": "ORD123"
}',
                            'extra' => 'Use FAILED if dispense fails — a refund is triggered automatically.',
                        ],
                    ] as $card)
                        <div class="rounded-lg border border-gray-200 p-4">
                            <p class="font-medium text-gray-900">{{ $card['title'] }}</p>
                            <div class="mt-3">
                                <x-copy-url :url="$card['url']" compact />
                            </div>
                            @if($card['extra'])
                                <p class="mt-3 text-xs text-gray-600">{{ $card['extra'] }}</p>
                            @endif
                            <p class="mt-4 text-xs font-medium text-gray-700">Request</p>
                            <pre class="mt-1 overflow-x-auto rounded-lg bg-gray-50 p-3 font-mono text-xs leading-relaxed text-gray-800">{{ $card['request'] }}</pre>
                            <p class="mt-3 text-xs font-medium text-gray-700">Response</p>
                            <pre class="mt-1 overflow-x-auto rounded-lg bg-gray-50 p-3 font-mono text-xs leading-relaxed text-gray-800">{{ $card['response'] }}</pre>
                        </div>
                    @endforeach
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-medium text-gray-900">Request signature (create order)</p>
                    <p class="mt-1 text-gray-600">
                        Required when signature verification is enabled. Use the machine secret key from
                        <a href="{{ route('machines.index') }}" class="text-brand hover:underline">Machines</a>.
                    </p>
                    <ol class="mt-3 list-decimal space-y-1 pl-5 text-gray-700">
                        <li>Sort <code class="rounded bg-gray-100 px-1">secret_key</code>, <code class="rounded bg-gray-100 px-1">timestamp</code>, and <code class="rounded bg-gray-100 px-1">randstr</code> alphabetically.</li>
                        <li>Concatenate the three values (no separator).</li>
                        <li>Set <code class="rounded bg-gray-100 px-1">sign = SHA1(result)</code>.</li>
                    </ol>
                    <p class="mt-3 text-xs text-gray-500">
                        <code class="rounded bg-gray-100 px-1">timestamp</code> format: <code class="rounded bg-gray-100 px-1">YmdHis</code> (e.g. 20260610143000).
                        Phone numbers: international format <code class="rounded bg-gray-100 px-1">2567XXXXXXXX</code> or local <code class="rounded bg-gray-100 px-1">07XXXXXXXX</code>.
                    </p>
                </div>
            </div>
        </div>
    </x-dashboard-layout>
</x-app-layout>
