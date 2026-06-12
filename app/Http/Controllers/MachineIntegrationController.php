<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MachineIntegrationController extends Controller
{
    public function __invoke(): View
    {
        return view('integration.index', [
            'apiBaseUrl' => rtrim((string) config('app.url'), '/').'/api/vending',
            'ipnUrl' => route('cellulant.ipn'),
        ]);
    }
}
