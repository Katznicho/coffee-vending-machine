<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Order;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Support\OrderReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('orders.index');
    }

    public function create(): View
    {
        $machines = Machine::query()->orderBy('name', 'asc')->get();

        return view('orders.create', compact('machines'));
    }

    public function store(Request $request, PaymentProviderInterface $paymentProvider): RedirectResponse
    {
        $validated = $request->validate([
            'machine_id' => 'required|string|exists:machines,machine_id',
            'product_name' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'customer_phone' => 'required|string|max:20',
        ]);

        $order = Order::create([
            'machine_order_id' => 'ADM-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4)),
            'third_party_order_id' => OrderReference::thirdPartyId(),
            'machine_id' => $validated['machine_id'],
            'product_name' => $validated['product_name'],
            'amount' => $validated['amount'],
            'channel_id' => config('vending.default_channel_id', '36'),
            'customer_phone' => $validated['customer_phone'],
            'payment_status' => 'pending',
            'dispense_status' => 'pending',
            'expires_at' => now()->addMinutes(config('vending.order_expiry_minutes', 15)),
        ]);

        $payment = $paymentProvider->initiateCollection($order, $validated['customer_phone']);

        if ($payment->status === 'failed') {
            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Order created, but the payment prompt could not be initiated. Check the IPN logs and payment details.');
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order created and Mobile Money prompt sent to '.$validated['customer_phone'].'.');
    }

    public function show(Order $order): View
    {
        $order->load(['machine', 'payments']);

        return view('orders.show', compact('order'));
    }

    public function checkStatus(Order $order, PaymentProviderInterface $paymentProvider): RedirectResponse
    {
        $previousStatus = $order->payment_status;
        $order = $paymentProvider->refreshPaymentStatus($order->fresh());

        $message = match (true) {
            $order->payment_status === 'paid' && $previousStatus !== 'paid' => 'Cellulant confirms this payment is now paid.',
            $order->payment_status === 'paid' => 'This order is already marked as paid.',
            $order->payment_status === 'failed' => 'Cellulant reports this payment as failed.',
            default => 'No completed payment found on Cellulant yet. The order is still pending.',
        };

        return redirect()
            ->route('orders.show', $order)
            ->with('success', $message);
    }
}
