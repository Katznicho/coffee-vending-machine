<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineController extends Controller
{
    public function index(): View
    {
        return view('machines.index');
    }

    public function create(): View
    {
        return view('machines.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'machine_id' => 'required|string|max:20|unique:machines,machine_id',
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'secret_key' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        Machine::create($validated);

        return redirect()->route('machines.index')->with('success', 'Machine created.');
    }

    public function edit(Machine $machine): View
    {
        return view('machines.edit', compact('machine'));
    }

    public function update(Request $request, Machine $machine): RedirectResponse
    {
        $validated = $request->validate([
            'machine_id' => 'required|string|max:20|unique:machines,machine_id,'.$machine->id,
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'secret_key' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $machine->update($validated);

        return redirect()->route('machines.index')->with('success', 'Machine updated.');
    }
}
