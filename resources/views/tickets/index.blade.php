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

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="min-w-full text-sm">
                <thead class="bg-neutral-50 dark:bg-neutral-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">ID</th>
                        <th class="px-4 py-3 text-left font-medium">Naam</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Prioriteit</th>
                        <th class="px-4 py-3 text-left font-medium">Developer</th>
                        <th class="px-4 py-3 text-left font-medium">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="px-4 py-3">{{ $ticket->id }}</td>
                            <td class="px-4 py-3">{{ $ticket->naam }}</td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $ticket->status === 'gesloten' ? 'green' : ($ticket->status === 'in_behandeling' ? 'amber' : 'red') }}" size="sm">
                                    {{ $ticket->statusLabel() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $ticket->prioriteitKleur() }}" size="sm">
                                    {{ ucfirst($ticket->prioriteit) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                {{ $ticket->developer?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <flux:button href="{{ route('tickets.show', $ticket) }}" size="sm" variant="ghost">Bekijk</flux:button>
                                    <flux:button href="{{ route('tickets.edit', $ticket) }}" size="sm" variant="ghost">Bewerk</flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-neutral-500">{{ __('Geen tickets gevonden') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $tickets->links() }}
    </div>
</x-layouts::app>
