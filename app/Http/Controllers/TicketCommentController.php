<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    /**
     * Store a new comment for the ticket.
     */
    public function store(Request $request, Ticket $ticket)
    {
        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $this->validatedBody($request),
        ]);

        $ticket->recordActivity(
            type: 'commented',
            description: __('Reactie geplaatst'),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', __('Reactie geplaatst.'));
    }

    /**
     * Show the form for editing the given comment.
     */
    public function edit(Request $request, Ticket $ticket, TicketComment $comment)
    {
        abort_unless($comment->isOwnedBy($request->user()), 403);

        return view('tickets.comments.edit', compact('ticket', 'comment'));
    }

    /**
     * Update the given comment.
     */
    public function update(Request $request, Ticket $ticket, TicketComment $comment)
    {
        abort_unless($comment->isOwnedBy($request->user()), 403);

        $comment->update([
            'body' => $this->validatedBody($request),
        ]);

        $ticket->recordActivity(
            type: 'comment_updated',
            description: __('Reactie bijgewerkt'),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', __('Reactie bijgewerkt.'));
    }

    /**
     * Remove the given comment.
     */
    public function destroy(Request $request, Ticket $ticket, TicketComment $comment)
    {
        abort_unless($comment->isDeletableBy($request->user()), 403);

        $comment->delete();

        $ticket->recordActivity(
            type: 'comment_removed',
            description: __('Reactie verwijderd'),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', __('Reactie verwijderd.'));
    }

    /**
     * Validate the comment body.
     */
    private function validatedBody(Request $request): string
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], [
            'body.required' => __('Schrijf eerst een reactie.'),
            'body.max' => __('Een reactie mag maximaal 5000 tekens bevatten.'),
        ], [
            'body' => __('reactie'),
        ]);

        return $validated['body'];
    }
}
