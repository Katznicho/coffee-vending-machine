<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Order;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'machinesCount' => Machine::count(),
            'ordersToday' => Order::whereDate('created_at', today())->count(),
            'paidToday' => Order::whereDate('paid_at', today())->where('payment_status', 'paid')->count(),
            'revenueToday' => Order::whereDate('paid_at', today())->where('payment_status', 'paid')->sum('amount'),
        ]);
    }
}
