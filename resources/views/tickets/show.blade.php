<x-layouts::app :title="__('Ticket #') . $ticket->id">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading>{{ __('Ticket #') . $ticket->id }}</flux:heading>

        <div class="flex max-w-lg flex-col gap-3 rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            <div>
                <div class="text-sm font-medium text-neutral-500">{{ __('Naam') }}</div>
                <div>{{ $ticket->naam }}</div>
            </div>

            <div>
                <div class="text-sm font-medium text-neutral-500">{{ __('Beschrijving') }}</div>
                <div>{{ $ticket->beschrijving ?: '—' }}</div>
            </div>

            <div>
                <div class="text-sm font-medium text-neutral-500">{{ __('Status') }}</div>
                <flux:badge color="{{ $ticket->status === 'gesloten' ? 'green' : ($ticket->status === 'in_behandeling' ? 'amber' : 'red') }}" size="sm">
                    {{ $ticket->status }}
                </flux:badge>
            </div>

            <div>
                <div class="text-sm font-medium text-neutral-500">{{ __('Gemaakt op') }}</div>
                <div>{{ $ticket->created_at->format('d-m-Y H:i') }}</div>
            </div>

            <div class="flex gap-3 pt-2">
                <flux:button href="{{ route('tickets.edit', $ticket) }}" variant="primary">
                    {{ __('Bewerk') }}
                </flux:button>
                <form action="{{ route('tickets.destroy', $ticket) }}" method="POST"
                      onsubmit="return confirm('{{ __('Ticket verwijderen?') }}')">
                    @csrf
                    @method('DELETE')
                    <flux:button type="submit" variant="danger">
                        {{ __('Verwijder') }}
                    </flux:button>
                </form>
            </div>
        </div>
    </div>
</x-layouts::app>
