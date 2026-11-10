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

            @if (empty($categories))
                <flux:callout icon="exclamation-triangle" variant="warning">
                    {{ __('Er zijn nog geen actieve categorieën. Vraag een beheerder om er een aan te maken.') }}
                </flux:callout>
            @else
                <flux:select name="categorie" label="{{ __('Categorie') }}" required>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(old('categorie', $defaultCategory) === $value)>{{ $label }}</option>
                    @endforeach
                </flux:select>
            @endif

            @if (! empty($applications))
                <flux:select name="applicatie" label="{{ __('Applicatie/website (optioneel)') }}">
                    <option value="">{{ __('Geen specifieke applicatie') }}</option>
                    @foreach ($applications as $value => $label)
                        <option value="{{ $value }}" @selected((string) old('applicatie') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </flux:select>
            @endif

            <p class="text-sm text-neutral-500">
                {{ __('Een nieuw ticket krijgt automatisch de status Nieuw en prioriteit Normaal.') }}
            </p>

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('tickets.index') }}" variant="ghost">
                    {{ __('Annuleer') }}
                </flux:button>
                <flux:button type="submit" variant="primary" :disabled="empty($categories)">
                    {{ __('Opslaan') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
