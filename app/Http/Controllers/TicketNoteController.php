<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketNote;
use Illuminate\Http\Request;

class TicketNoteController extends Controller
{
    /**
     * Store a private note for the current user.
     */
    public function store(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ], [
            'note.required' => __('Schrijf eerst een notitie.'),
            'note.max' => __('Een notitie mag maximaal 5000 tekens bevatten.'),
        ], [
            'note' => __('notitie'),
        ]);

        $ticket->notes()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['note'],
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', __('Notitie opgeslagen. Alleen jij kunt deze zien.'));
    }

    /**
     * Remove one of the current user's own notes.
     */
    public function destroy(Request $request, Ticket $ticket, TicketNote $note)
    {
        abort_unless($note->isOwnedBy($request->user()), 403);

        $note->delete();

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', __('Notitie verwijderd.'));
    }
}
