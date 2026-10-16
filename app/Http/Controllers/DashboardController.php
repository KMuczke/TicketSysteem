<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $openStatuses = ['open', 'in_behandeling'];

        $stats = [
            'totaal' => Ticket::count(),
            'niet_toegewezen' => Ticket::whereNull('developer_id')->count(),
            'kritiek' => Ticket::where('prioriteit', 'kritiek')
                ->whereIn('status', $openStatuses)
                ->count(),
            'open' => Ticket::whereIn('status', $openStatuses)->count(),
        ];

        $gemiddeldeUren = Ticket::where('status', 'gesloten')
            ->whereNotNull('gesloten_op')
            ->get()
            ->avg(fn (Ticket $ticket) => $ticket->created_at->diffInHours($ticket->gesloten_op));

        $gemiddeldeDoorloopijd = null;

        if ($gemiddeldeUren !== null) {
            $gemiddeldeDoorloopijd = $gemiddeldeUren >= 24
                ? number_format($gemiddeldeUren / 24, 1, ',', '.').' dagen'
                : number_format((float) $gemiddeldeUren, 1, ',', '.').' uur';
        }

        $statusVolgorde = ['open' => 'Open', 'in_behandeling' => 'In behandeling'];
        $statusKleuren = ['open' => '#ef4444', 'in_behandeling' => '#f59e0b'];

        $openPerStatus = Ticket::whereIn('status', $openStatuses)
            ->select('status', DB::raw('COUNT(*) as aantal'))
            ->groupBy('status')
            ->pluck('aantal', 'status');

        $statusChart = [
            'labels' => [],
            'data' => [],
            'kleuren' => [],
        ];

        foreach ($statusVolgorde as $status => $label) {
            $statusChart['labels'][] = $label;
            $statusChart['data'][] = (int) ($openPerStatus[$status] ?? 0);
            $statusChart['kleuren'][] = $statusKleuren[$status];
        }

        $openPerDeveloper = Ticket::whereIn('status', $openStatuses)
            ->leftJoin('users', 'users.id', '=', 'tickets.developer_id')
            ->selectRaw("COALESCE(users.name, 'Niet toegewezen') as developer, COUNT(*) as aantal")
            ->groupBy('developer')
            ->orderByDesc('aantal')
            ->pluck('aantal', 'developer');

        $developerChart = [
            'labels' => $openPerDeveloper->keys()->all(),
            'data' => $openPerDeveloper->map(fn ($aantal) => (int) $aantal)->values()->all(),
        ];

        return view('dashboard', compact(
            'stats',
            'gemiddeldeDoorloopijd',
            'statusChart',
            'developerChart',
        ));
    }
}
