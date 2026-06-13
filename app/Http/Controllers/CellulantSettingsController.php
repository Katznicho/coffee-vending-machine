<?php

namespace App\Http\Controllers;

use App\Models\CellulantSetting;
use App\Services\CellulantSandboxTester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CellulantSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = CellulantSetting::current();

        return view('settings.cellulant', [
            'settings' => $settings,
            'ipnUrl' => route('cellulant.ipn'),
            'oauthUrl' => $settings->isSandbox()
                ? 'https://accounts.sandbox.tingg.africa/api/v1/oauth/token'
                : 'https://accounts.tingg.africa/api/v1/oauth/token',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'environment' => 'required|in:sandbox,production',
            'sandbox_base_url' => 'nullable|url|max:255',
            'sandbox_username' => 'nullable|string|max:255',
            'sandbox_password' => 'nullable|string|max:255',
            'sandbox_counter_code' => 'nullable|string|max:50',
            'production_base_url' => 'nullable|url|max:255',
            'production_username' => 'nullable|string|max:255',
            'production_password' => 'nullable|string|max:255',
            'production_counter_code' => 'nullable|string|max:50',
            'initiate_payment_path' => 'required|string|max:255',
            'default_payer_client_code' => 'required|string|max:50',
            'airtel_payer_client_code' => 'required|string|max:50',
            'auto_detect_payer' => 'nullable|boolean',
            'country_code' => 'required|string|max:3',
            'currency_code' => 'required|string|max:3',
            'request_origin_code' => 'required|string|max:100',
            'oauth_scope' => 'required|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $settings = CellulantSetting::current();

        $settings->fill([
            'environment' => $validated['environment'],
            'sandbox_base_url' => $validated['sandbox_base_url'],
            'sandbox_username' => $validated['sandbox_username'],
            'sandbox_counter_code' => $validated['sandbox_counter_code'],
            'production_base_url' => $validated['production_base_url'],
            'production_username' => $validated['production_username'],
            'production_counter_code' => $validated['production_counter_code'],
            'initiate_payment_path' => $validated['initiate_payment_path'],
            'default_payer_client_code' => $validated['default_payer_client_code'],
            'airtel_payer_client_code' => $validated['airtel_payer_client_code'],
            'auto_detect_payer' => $request->boolean('auto_detect_payer'),
            'country_code' => strtoupper($validated['country_code']),
            'currency_code' => strtoupper($validated['currency_code']),
            'request_origin_code' => $validated['request_origin_code'],
            'oauth_scope' => $validated['oauth_scope'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($validated['sandbox_password'])) {
            $settings->sandbox_password = $validated['sandbox_password'];
        }

        if (! empty($validated['production_password'])) {
            $settings->production_password = $validated['production_password'];
        }

        $settings->save();

        return redirect()
            ->route('settings.cellulant.edit')
            ->with('success', 'Cellulant settings updated.');
    }

    public function testConnectivity(CellulantSandboxTester $tester): RedirectResponse
    {
        return redirect()
            ->route('settings.cellulant.edit')
            ->with('test_results', $tester->runConnectivity());
    }

    public function testPayment(Request $request, CellulantSandboxTester $tester): RedirectResponse
    {
        $validated = $request->validate([
            'test_phone' => 'required|string|max:20',
            'test_amount' => 'nullable|integer|min:1|max:500000',
        ]);

        return redirect()
            ->route('settings.cellulant.edit')
            ->with('test_results', $tester->runPaymentTest(
                testPhone: $validated['test_phone'],
                testAmount: (int) ($validated['test_amount'] ?? 1000),
            ))
            ->withInput($request->only('test_phone', 'test_amount'));
    }
}
