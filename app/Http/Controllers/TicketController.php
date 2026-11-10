<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'prioriteit' => ['nullable', Rule::enum(TicketPriority::class)],
            'categorie' => ['nullable', 'string', Rule::exists('categories', 'slug')],
            'applicatie' => ['nullable', 'integer', Rule::exists('applications', 'id')],
            'toegewezen' => [
                'nullable',
                Rule::in([
                    'geen',
                    ...User::query()
                        ->where('role', UserRole::Developer)
                        ->pluck('id')
                        ->map(fn (int $id) => (string) $id)
                        ->all(),
                ]),
            ],
            'mijn' => ['nullable', 'boolean'],
        ]);

        $filters = [
            'status' => $request->input('status'),
            'prioriteit' => $request->input('prioriteit'),
            'categorie' => $request->input('categorie'),
            'applicatie' => $request->input('applicatie'),
            'toegewezen' => $request->input('toegewezen'),
            'mijn' => $request->boolean('mijn'),
        ];

        $tickets = Ticket::query()
            ->with(['user', 'assignee', 'category', 'application'])
            ->when($request->user()->isMelder(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['prioriteit'], fn ($query, $priority) => $query->where('prioriteit', $priority))
            ->when($filters['categorie'], fn ($query, $category) => $query->where('categorie', $category))
            ->when($filters['applicatie'], fn ($query, $application) => $query->where('application_id', $application))
            ->when($filters['mijn'], fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($filters['toegewezen'] === 'geen', fn ($query) => $query->whereNull('assignee_id'))
            ->when(
                $filters['toegewezen'] && $filters['toegewezen'] !== 'geen',
                fn ($query) => $query->where('assignee_id', $filters['toegewezen']),
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'hasFilters' => $filters['mijn'] || collect($filters)->except('mijn')->filter()->isNotEmpty(),
            'statuses' => TicketStatus::options(),
            'priorities' => TicketPriority::options(),
            'categories' => Category::query()->orderBy('naam')->pluck('naam', 'slug'),
            'applications' => Application::query()->orderBy('naam')->pluck('naam', 'id'),
            'developers' => User::query()
                ->where('role', UserRole::Developer)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tickets.create', [
            'categories' => Category::options(),
            'defaultCategory' => Category::defaultSlug(),
            'applications' => Application::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255',
            'beschrijving' => 'nullable|string',
            'categorie' => [
                'required',
                Rule::exists('categories', 'slug')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'applicatie' => [
                'nullable',
                Rule::exists('applications', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = TicketStatus::Nieuw->value;
        $validated['prioriteit'] = TicketPriority::Normaal->value;
        $validated['application_id'] = $validated['applicatie'] ?? null;

        unset($validated['applicatie']);

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
            'category',
            'application',
            'comments.user',
            'activities.user',
        ]);

        $notes = $ticket->notes()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('tickets.show', [
            'ticket' => $ticket,
            'notes' => $notes,
            'categories' => Category::optionsIncluding($ticket->categorie),
            'priorities' => TicketPriority::options(),
        ]);
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
     * Change the category and priority of a ticket.
     */
    public function updateClassification(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->isClassificationEditableBy($request->user()), 403);

        $validated = $request->validate([
            'categorie' => [
                'required',
                Rule::exists('categories', 'slug')->where(function ($query) use ($ticket) {
                    $query->where('is_active', true)->orWhere('slug', $ticket->categorie);
                }),
            ],
            'prioriteit' => ['required', Rule::enum(TicketPriority::class)],
        ]);

        $ticket->update($validated);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Classificatie van ticket {$ticket->ticket_number} is bijgewerkt.");
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
