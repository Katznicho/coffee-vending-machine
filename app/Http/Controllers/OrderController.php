<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('orders.index');
    }

    public function show(Order $order): View
    {
        $order->load(['machine', 'payments']);

        return view('orders.show', compact('order'));
    }
}
