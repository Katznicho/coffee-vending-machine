<?php

namespace App\Http\Middleware;

use App\Models\Machine;
use App\Support\MachineSignature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMachineRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('vending.verify_signature', true)) {
            return $next($request);
        }

        $machineId = (string) $request->input('machid', $request->input('machineId', $request->input('machine_id', '')));
        $timestamp = (string) $request->input('timestamp', '');
        $randstr = (string) $request->input('randstr', '');
        $sign = (string) $request->input('sign', '');

        if ($machineId === '' && $request->filled(['orderid', 'orderId'])) {
            $orderId = $request->input('orderId', $request->input('orderid'));
            $order = \App\Models\Order::where('machine_order_id', $orderId)->first();
            $machineId = $order?->machine_id ?? '';
        }

        if ($machineId === '' || $timestamp === '' || $randstr === '' || $sign === '') {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Invalid request',
            ], 422);
        }

        $machine = Machine::where('machine_id', $machineId)->first();

        if (! $machine || ! $machine->isActive()) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Machine not found',
            ], 404);
        }

        if (! MachineSignature::verify($machine->secret_key, $timestamp, $randstr, $sign)) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Invalid signature',
            ], 403);
        }

        $request->attributes->set('machine', $machine);

        return $next($request);
    }
}
