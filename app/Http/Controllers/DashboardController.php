<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with an overview of all tickets and their status.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Melders only see their own tickets; developers and administrators
        // get an overview of every ticket in the system.
        $base = Ticket::query();

        if ($user->isMelder()) {
            $base->where('user_id', $user->id);
        }

        $statusCounts = (clone $base)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $openStatuses = collect(TicketStatus::cases())
            ->filter(fn (TicketStatus $status) => $status->isOpen())
            ->map(fn (TicketStatus $status) => $status->value)
            ->all();

        $priorityCounts = (clone $base)
            ->whereIn('status', $openStatuses)
            ->select('prioriteit', DB::raw('count(*) as total'))
            ->groupBy('prioriteit')
            ->pluck('total', 'prioriteit');

        $statusOverview = collect(TicketStatus::cases())
            ->mapWithKeys(fn (TicketStatus $status) => [
                $status->value => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                    'count' => (int) ($statusCounts[$status->value] ?? 0),
                    'open' => $status->isOpen(),
                ],
            ]);

        $priorityOverview = collect(TicketPriority::cases())
            ->mapWithKeys(fn (TicketPriority $priority) => [
                $priority->value => [
                    'value' => $priority->value,
                    'label' => $priority->label(),
                    'color' => $priority->color(),
                    'count' => (int) ($priorityCounts[$priority->value] ?? 0),
                ],
            ]);

        $developerCounts = (clone $base)
            ->whereNotNull('assignee_id')
            ->whereIn('status', $openStatuses)
            ->select('assignee_id', DB::raw('count(*) as total'))
            ->groupBy('assignee_id')
            ->pluck('total', 'assignee_id');

        $developerOverview = User::query()
            ->where('role', UserRole::Developer)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $developer) => [
                $developer->id => [
                    'name' => $developer->name,
                    'count' => (int) ($developerCounts[$developer->id] ?? 0),
                ],
            ]);

        $totalCount = (int) $statusCounts->sum();
        $openCount = (int) $statusOverview
            ->where('open', true)
            ->sum('count');
        $closedCount = $statusOverview[TicketStatus::Gesloten->value]['count'];
        $unassignedOpenCount = (clone $base)
            ->whereNull('assignee_id')
            ->whereIn('status', $openStatuses)
            ->count();

        $tickets = (clone $base)
            ->with(['user', 'assignee', 'category'])
            ->latest()
            ->paginate(25);

        return view('dashboard', [
            'tickets' => $tickets,
            'statusOverview' => $statusOverview,
            'priorityOverview' => $priorityOverview,
            'developerOverview' => $developerOverview,
            'totalCount' => $totalCount,
            'openCount' => $openCount,
            'closedCount' => $closedCount,
            'unassignedOpenCount' => $unassignedOpenCount,
        ]);
    }
}
