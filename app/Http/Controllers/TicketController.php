<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tickets = Ticket::with(['user', 'assignee'])->latest()->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255',
            'beschrijving' => 'nullable|string',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = TicketStatus::Nieuw->value;

        $ticket = Ticket::create($validated);

        return redirect()
            ->route('tickets.index')
            ->with('success', "Ticket {$ticket->ticket_number} is aangemaakt.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load([
            'user',
            'assignee',
            'comments.user',
            'activities.user',
        ]);

        $notes = $ticket->notes()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('tickets.show', compact('ticket', 'notes'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        abort_unless($ticket->isEditableBy(auth()->user()), 403);

        return view('tickets.edit', compact('ticket'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->isEditableBy($request->user()), 403);

        $validated = $request->validate([
            'naam' => 'required|string|max:255',
            'beschrijving' => 'nullable|string',
        ]);

        $ticket->update($validated);

        return redirect()
            ->route('tickets.index')
            ->with('success', "Ticket {$ticket->ticket_number} is bijgewerkt.");
    }

    /**
     * Assign the ticket to the authenticated developer.
     */
    public function assign(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->isAssignableBy($request->user()), 403);

        if ($ticket->assignee_id !== $request->user()->id || $ticket->status !== TicketStatus::Toegewezen->value) {
            $ticket->update([
                'assignee_id' => $request->user()->id,
                'status' => TicketStatus::Toegewezen->value,
            ]);

            $ticket->recordActivity(
                type: 'assigned',
                description: __('Ticket opgepakt'),
            );
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} is aan jou toegewezen.");
    }

    /**
     * Release the ticket so it becomes available again.
     */
    public function release(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->isReleasableBy($request->user()), 403);

        $ticket->update([
            'assignee_id' => null,
            'status' => TicketStatus::Nieuw->value,
        ]);

        $ticket->recordActivity(
            type: 'released',
            description: __('Ticket losgelaten'),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} is weer vrijgegeven.");
    }

    /**
     * Change the status of a ticket following the allowed lifecycle transitions.
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
        ]);

        $newStatus = TicketStatus::from($validated['status']);

        abort_unless(
            in_array($newStatus, $ticket->availableStatusTransitionsFor($request->user()), true),
            403
        );

        $ticket->update([
            'status' => $newStatus->value,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Status van ticket {$ticket->ticket_number} is gewijzigd naar {$newStatus->label()}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'Ticket verwijderd.');
    }
}
