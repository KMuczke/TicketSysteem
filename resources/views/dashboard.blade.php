<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:text class="text-neutral-500">
                @if (auth()->user()->isMelder())
                    {{ __('Overzicht van je eigen tickets en hun status.') }}
                @else
                    {{ __('Overzicht van alle tickets en hun status.') }}
                @endif
            </flux:text>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:text class="text-sm text-neutral-500">{{ __('Totaal tickets') }}</flux:text>
                <p class="mt-1 text-2xl font-semibold">{{ $totalCount }}</p>
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:text class="text-sm text-neutral-500">{{ __('Open tickets') }}</flux:text>
                <p class="mt-1 text-2xl font-semibold">{{ $openCount }}</p>
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:text class="text-sm text-neutral-500">{{ __('Gesloten tickets') }}</flux:text>
                <p class="mt-1 text-2xl font-semibold">{{ $closedCount }}</p>
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:text class="text-sm text-neutral-500">{{ __('Niet toegewezen') }}</flux:text>
                <p class="mt-1 text-2xl font-semibold">{{ $unassignedOpenCount }}</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="sm">{{ __('Open tickets per status') }}</flux:heading>

                <div class="mt-3 flex flex-col gap-2">
                    @foreach ($statusOverview->where('open', true) as $status)
                        <div class="flex items-center justify-between gap-3">
                            <flux:badge color="{{ $status['color'] }}" size="sm">
                                {{ $status['label'] }}
                            </flux:badge>
                            <span class="text-sm font-medium">{{ $status['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="sm">{{ __('Open tickets per prioriteit') }}</flux:heading>

                <div class="mt-3 flex flex-col gap-2">
                    @foreach ($priorityOverview as $priority)
                        <div class="flex items-center justify-between gap-3">
                            <flux:badge color="{{ $priority['color'] }}" size="sm">
                                {{ $priority['label'] }}
                            </flux:badge>
                            <span class="text-sm font-medium">{{ $priority['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="sm">{{ __('Open tickets per developer') }}</flux:heading>

                <div class="mt-3 flex flex-col gap-2">
                    @forelse ($developerOverview as $developer)
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm">{{ $developer['name'] }}</span>
                            <span class="text-sm font-medium">{{ $developer['count'] }}</span>
                        </div>
                    @empty
                        <flux:text class="text-sm text-neutral-500">
                            {{ __('Er zijn nog geen developers.') }}
                        </flux:text>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">
                    {{ auth()->user()->isMelder() ? __('Mijn tickets') : __('Alle tickets') }}
                </flux:heading>

                <flux:button href="{{ route('tickets.create') }}" variant="primary">
                    {{ __('Nieuw ticket') }}
                </flux:button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-neutral-50 dark:bg-neutral-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Nummer</th>
                            <th class="px-4 py-3 text-left font-medium">Naam</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Prioriteit</th>
                            <th class="px-4 py-3 text-left font-medium">Categorie</th>
                            <th class="px-4 py-3 text-left font-medium">Toegewezen</th>
                            <th class="px-4 py-3 text-left font-medium">Aangemaakt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="px-4 py-3 font-mono">
                                    <a href="{{ route('tickets.show', $ticket) }}"
                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $ticket->ticket_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="hover:underline">
                                        {{ $ticket->naam }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge color="{{ $ticket->statusColor() }}" size="sm">
                                        {{ $ticket->statusLabel($ticket->status) }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge color="{{ $ticket->priorityColor() }}" size="sm">
                                        {{ $ticket->priorityLabel($ticket->prioriteit) }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge color="{{ $ticket->category?->kleur ?? 'zinc' }}" size="sm">
                                        {{ $ticket->category?->naam ?? $ticket->categorie }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($ticket->assignee)
                                        {{ $ticket->assignee->name }}
                                        @if ($ticket->assignee_id === auth()->id())
                                            <span class="text-xs text-neutral-500">({{ __('jij') }})</span>
                                        @endif
                                    @else
                                        <span class="text-neutral-500">{{ __('Niet toegewezen') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $ticket->created_at?->format('d-m-Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-neutral-500">
                                    {{ __('Geen tickets gevonden') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tickets->links() }}
        </div>
    </div>
</x-layouts::app>
