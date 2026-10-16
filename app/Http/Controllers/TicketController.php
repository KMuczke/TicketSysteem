<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tickets = Ticket::with('developer')->latest()->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $developers = User::orderBy('name')->get();

        return view('tickets.create', compact('developers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255',
            'beschrijving' => 'nullable|string',
            'status' => 'required|string|in:open,in_behandeling,gesloten',
            'prioriteit' => 'nullable|string|in:laag,gemiddeld,hoog,kritiek',
            'developer_id' => 'nullable|integer|exists:users,id',
        ]);

        if (! isset($validated['prioriteit'])) {
            $validated['prioriteit'] = 'gemiddeld';
        }

        $validated['gesloten_op'] = $validated['status'] === 'gesloten' ? now() : null;

        Ticket::create($validated);

        return redirect()->route('tickets.index')->with('success', 'Ticket aangemaakt.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load('developer');

        return view('tickets.show', compact('ticket'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        $developers = User::orderBy('name')->get();

        return view('tickets.edit', compact('ticket', 'developers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255',
            'beschrijving' => 'nullable|string',
            'status' => 'required|string|in:open,in_behandeling,gesloten',
            'prioriteit' => 'nullable|string|in:laag,gemiddeld,hoog,kritiek',
            'developer_id' => 'nullable|integer|exists:users,id',
        ]);

        if (! isset($validated['prioriteit'])) {
            $validated['prioriteit'] = 'gemiddeld';
        }

        if ($validated['status'] === 'gesloten' && $ticket->status !== 'gesloten') {
            $validated['gesloten_op'] = now();
        } elseif ($validated['status'] !== 'gesloten') {
            $validated['gesloten_op'] = null;
        }

        $ticket->update($validated);

        return redirect()->route('tickets.index')->with('success', 'Ticket bijgewerkt.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'Ticket verwijderd.');
    }
}
