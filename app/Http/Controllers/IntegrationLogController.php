<?php

namespace App\Http\Controllers;

use App\Models\IntegrationLog;
use Illuminate\View\View;

class IntegrationLogController extends Controller
{
    public function index(): View
    {
        return view('integration-logs.index');
    }

    public function show(IntegrationLog $integrationLog): View
    {
        $integrationLog->load('order');

        return view('integration-logs.show', compact('integrationLog'));
    }
}
