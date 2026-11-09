<x-layouts::app :title="__('Ticket bewerken')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading>{{ __('Ticket bewerken') }}</flux:heading>

        <form action="{{ route('tickets.update', $ticket) }}" method="POST"
              class="flex max-w-lg flex-col gap-4 rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            @csrf
            @method('PUT')

            <flux:input name="naam" label="{{ __('Naam') }}" :value="old('naam', $ticket->naam)" required />

            <flux:textarea name="beschrijving" label="{{ __('Beschrijving') }}" rows="4">
                {{ old('beschrijving', $ticket->beschrijving) }}
            </flux:textarea>

            <div class="flex items-center gap-2 text-sm text-neutral-500">
                <span>{{ __('Status') }}:</span>
                <flux:badge color="{{ $ticket->statusColor() }}" size="sm">
                    {{ $ticket->statusLabel($ticket->status) }}
                </flux:badge>
                <span>{{ __('(wijzig je via de ticketdetailpagina)') }}</span>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('tickets.index') }}" variant="ghost">
                    {{ __('Annuleer') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Opslaan') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
