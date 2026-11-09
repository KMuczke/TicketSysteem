<x-layouts::app :title="__('Reactie bewerken')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading size="xl">{{ __('Reactie bewerken') }}</flux:heading>

        <p class="text-sm text-neutral-500">
            {{ __('Ticket') }} {{ $ticket->ticket_number }} &middot; {{ $ticket->naam }}
        </p>

        @if ($errors->any())
            <flux:callout icon="exclamation-triangle" variant="danger">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        <form action="{{ route('tickets.comments.update', [$ticket, $comment]) }}" method="POST"
              class="flex max-w-lg flex-col gap-4 rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            @csrf
            @method('PUT')

            <flux:textarea name="body" label="{{ __('Reactie') }}" rows="4" required>{{ old('body', $comment->body) }}</flux:textarea>

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('tickets.show', $ticket) }}" variant="ghost">
                    {{ __('Annuleer') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Opslaan') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
