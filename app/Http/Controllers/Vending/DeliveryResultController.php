<?php

namespace App\Http\Controllers\Vending;

use App\Http\Controllers\Controller;
use App\Jobs\RefundOrderJob;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryResultController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orderid' => 'required|string|max:100',
            'torderid' => 'nullable|string|max:100',
            'status' => 'required',
        ]);

        Log::info('Delivery result received', [
            'ip' => $request->ip(),
            'orderid' => $validated['orderid'],
            'torderid' => $validated['torderid'] ?? null,
            'status' => $validated['status'],
        ]);

        $order = Order::query()
            ->when($validated['torderid'] ?? null, function ($query, $torderid) {
                $query->where('third_party_order_id', $torderid);
            }, function ($query) use ($validated) {
                $query->where('machine_order_id', $validated['orderid']);
            })
            ->first();

        if (! $order) {
            Log::warning('Delivery result order not found', [
                'orderid' => $validated['orderid'],
                'torderid' => $validated['torderid'] ?? null,
            ]);

            return response()->json([
                'code' => '0',
                'msg' => 'Order not found',
            ]);
        }

        if ((string) $validated['status'] === '1') {
            $order->update(['dispense_status' => 'success']);
            Log::info('Delivery marked success', ['order_id' => $order->id]);
        } else {
            $order->update(['dispense_status' => 'failed']);
            Log::warning('Delivery marked failed', [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
                'will_refund' => $order->payment_status === 'paid',
            ]);

            if ($order->payment_status === 'paid') {
                RefundOrderJob::dispatch($order->id);
            }
        }

        return response()->json([
            'code' => '1',
            'msg' => 'Received',
        ]);
    }
}
