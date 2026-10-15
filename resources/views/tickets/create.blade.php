<x-layouts::app :title="__('Ticket toevoegen')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading>{{ __('Ticket toevoegen') }}</flux:heading>

        <form action="{{ route('tickets.store') }}" method="POST"
              class="flex max-w-lg flex-col gap-4 rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            @csrf

            <flux:input name="naam" label="{{ __('Naam') }}" :value="old('naam')" required />

            <flux:textarea name="beschrijving" label="{{ __('Beschrijving') }}" rows="4">
                {{ old('beschrijving') }}
            </flux:textarea>

            <flux:select name="status" label="{{ __('Status') }}">
                <option value="open" @selected(old('status', 'open') === 'open')>Open</option>
                <option value="in_behandeling" @selected(old('status') === 'in_behandeling')>In behandeling</option>
                <option value="gesloten" @selected(old('status') === 'gesloten')>Gesloten</option>
            </flux:select>

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
