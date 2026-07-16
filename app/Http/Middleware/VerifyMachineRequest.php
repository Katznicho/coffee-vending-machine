<?php

namespace App\Http\Middleware;

use App\Models\Machine;
use App\Support\MachineSignature;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyMachineRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('vending.verify_signature', true)) {
            Log::info('Machine signature verification skipped', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

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
            Log::warning('Machine request missing auth fields', [
                'path' => $request->path(),
                'ip' => $request->ip(),
                'machid_present' => $machineId !== '',
                'timestamp_present' => $timestamp !== '',
                'randstr_present' => $randstr !== '',
                'sign_present' => $sign !== '',
                'payload_keys' => array_keys($request->all()),
            ]);

            return response()->json([
                'status' => 'FAILED',
                'message' => 'Invalid request',
            ], 422);
        }

        $machine = Machine::where('machine_id', $machineId)->first();

        if (! $machine || ! $machine->isActive()) {
            Log::warning('Machine not found or inactive', [
                'path' => $request->path(),
                'ip' => $request->ip(),
                'machid' => $machineId,
                'exists' => $machine !== null,
                'status' => $machine?->status,
            ]);

            return response()->json([
                'status' => 'FAILED',
                'message' => 'Machine not found',
            ], 404);
        }

        if (! MachineSignature::verify($machine->secret_key, $timestamp, $randstr, $sign)) {
            Log::warning('Machine signature invalid', [
                'path' => $request->path(),
                'ip' => $request->ip(),
                'machid' => $machineId,
                'timestamp' => $timestamp,
                'randstr' => $randstr,
                'sign_prefix' => substr($sign, 0, 8),
            ]);

            return response()->json([
                'status' => 'FAILED',
                'message' => 'Invalid signature',
            ], 403);
        }

        Log::info('Machine request authenticated', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'machid' => $machineId,
        ]);

        $request->attributes->set('machine', $machine);

        return $next($request);
    }
}
