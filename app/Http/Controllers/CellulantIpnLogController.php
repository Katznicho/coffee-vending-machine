<?php

namespace App\Http\Controllers;

use App\Models\CellulantIpnLog;
use Illuminate\View\View;

class CellulantIpnLogController extends Controller
{
    public function index(): View
    {
        return view('ipn-logs.index');
    }

    public function show(CellulantIpnLog $ipnLog): View
    {
        $ipnLog->load('order');

        return view('ipn-logs.show', compact('ipnLog'));
    }
}
