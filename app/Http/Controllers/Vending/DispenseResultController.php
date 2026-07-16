<?php

namespace App\Http\Controllers\Vending;

use App\Http\Controllers\Controller;
use App\Jobs\RefundOrderJob;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DispenseResultController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $transactionId = $request->input('transactionId')
            ?? $request->input('orderid')
            ?? $request->input('orderId');

        $status = strtoupper((string) ($request->input('status') ?? ''));

        Log::info('Dispense result received', [
            'ip' => $request->ip(),
            'transactionId' => $transactionId,
            'status' => $status,
            'torderid' => $request->input('torderid'),
        ]);

        $order = Order::query()
            ->when($request->input('torderid'), fn ($q, $id) => $q->where('third_party_order_id', $id))
            ->where(function ($query) use ($transactionId) {
                $query->where('machine_order_id', $transactionId)
                    ->orWhere('third_party_order_id', $transactionId);
            })
            ->first();

        if (! $order) {
            Log::warning('Dispense result order not found', [
                'ip' => $request->ip(),
                'transactionId' => $transactionId,
            ]);

            return response()->json([
                'status' => 'FAILED',
                'message' => 'Order not found',
            ], 404);
        }

        if (in_array($status, ['1', 'SUCCESS'], true)) {
            $order->update(['dispense_status' => 'success']);
            Log::info('Dispense marked success', [
                'order_id' => $order->id,
                'machine_order_id' => $order->machine_order_id,
            ]);
        } else {
            $order->update(['dispense_status' => 'failed']);

            Log::warning('Dispense marked failed', [
                'order_id' => $order->id,
                'machine_order_id' => $order->machine_order_id,
                'payment_status' => $order->payment_status,
                'will_refund' => $order->payment_status === 'paid',
            ]);

            if ($order->payment_status === 'paid') {
                RefundOrderJob::dispatch($order->id);
            }
        }

        return response()->json([
            'status' => 'RECEIVED',
            'transactionId' => $order->machine_order_id,
        ]);
    }
}
