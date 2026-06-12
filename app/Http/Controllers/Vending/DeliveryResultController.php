<?php

namespace App\Http\Controllers\Vending;

use App\Http\Controllers\Controller;
use App\Jobs\RefundOrderJob;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryResultController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orderid' => 'required|string|max:100',
            'torderid' => 'nullable|string|max:100',
            'status' => 'required',
        ]);

        $order = Order::query()
            ->when($validated['torderid'] ?? null, function ($query, $torderid) {
                $query->where('third_party_order_id', $torderid);
            }, function ($query) use ($validated) {
                $query->where('machine_order_id', $validated['orderid']);
            })
            ->first();

        if (! $order) {
            return response()->json([
                'code' => '0',
                'msg' => 'Order not found',
            ]);
        }

        if ((string) $validated['status'] === '1') {
            $order->update(['dispense_status' => 'success']);
        } else {
            $order->update(['dispense_status' => 'failed']);

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
