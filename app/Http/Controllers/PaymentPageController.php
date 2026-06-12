<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentProviders\PaymentProviderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentPageController extends Controller
{
    public function show(string $torderid): View|RedirectResponse
    {
        $order = Order::where('third_party_order_id', $torderid)->firstOrFail();
        $order->markExpiredIfNeeded();

        if ($order->payment_status === 'paid') {
            return view('pay.success', compact('order'));
        }

        if (in_array($order->payment_status, ['expired', 'failed', 'refunded'], true)) {
            return view('pay.expired', compact('order'));
        }

        return view('pay.show', compact('order'));
    }

    public function pay(Request $request, string $torderid, PaymentProviderInterface $paymentProvider): RedirectResponse
    {
        $order = Order::where('third_party_order_id', $torderid)->firstOrFail();
        $order->markExpiredIfNeeded();

        if ($order->payment_status !== 'pending') {
            return redirect()->route('pay.show', $torderid);
        }

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^(0|\+?256)\d{9}$/'],
        ]);

        $payment = $paymentProvider->initiateCollection($order, $validated['phone_number']);

        if ($payment->status === 'failed') {
            return back()->withErrors([
                'phone_number' => 'Unable to start payment. Please try again.',
            ]);
        }

        return redirect()
            ->route('pay.show', $torderid)
            ->with('status', 'Check your phone and approve the Mobile Money prompt.');
    }
}
