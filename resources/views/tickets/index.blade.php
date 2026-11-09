<x-layouts::app :title="__('Tickets')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <flux:heading>{{ __('Tickets') }}</flux:heading>
            <flux:button href="{{ route('tickets.create') }}" variant="primary">
                {{ __('Nieuw ticket') }}
            </flux:button>
        </div>

        @if (session('success'))
            <flux:callout icon="check-circle" variant="success">
                {{ session('success') }}
            </flux:callout>
        @endif

        @if (auth()->user()->isDeveloper() && auth()->user()->hasActiveAssignedTicket())
            <flux:callout icon="information-circle" variant="warning">
                {{ __('Je hebt al een ticket in behandeling. Rond dit eerst af of laat het los voordat je een nieuw ticket oppakt.') }}
            </flux:callout>
        @endif

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="min-w-full text-sm">
                <thead class="bg-neutral-50 dark:bg-neutral-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Nummer</th>
                        <th class="px-4 py-3 text-left font-medium">Naam</th>
                        <th class="px-4 py-3 text-left font-medium">Melder</th>
                        <th class="px-4 py-3 text-left font-medium">Toegewezen</th>
                        <th class="px-4 py-3 text-left font-medium">Aangemaakt</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="px-4 py-3 font-mono">{{ $ticket->ticket_number }}</td>
                            <td class="px-4 py-3">{{ $ticket->naam }}</td>
                            <td class="px-4 py-3">{{ $ticket->user?->name ?? __('Onbekend') }}</td>
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
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $ticket->statusColor() }}" size="sm">
                                    {{ $ticket->statusLabel($ticket->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <flux:button href="{{ route('tickets.show', $ticket) }}" size="sm" variant="ghost">
                                        {{ __('Bekijk') }}
                                    </flux:button>
                                    @if ($ticket->assignee_id === null && $ticket->isAssignableBy(auth()->user()))
                                        <form action="{{ route('tickets.assign', $ticket) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <flux:button type="submit" size="sm" variant="primary" icon="user-plus">
                                                {{ __('Oppakken') }}
                                            </flux:button>
                                        </form>
                                    @endif
                                    @if ($ticket->isReleasableBy(auth()->user()))
                                        <form action="{{ route('tickets.release', $ticket) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <flux:button type="submit" size="sm" variant="ghost" icon="arrow-uturn-left">
                                                {{ __('Loslaten') }}
                                            </flux:button>
                                        </form>
                                    @endif
                                    @if ($ticket->isEditableBy(auth()->user()))
                                        <flux:button href="{{ route('tickets.edit', $ticket) }}" size="sm" variant="ghost">
                                            {{ __('Bewerk') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
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
</x-layouts::app>
