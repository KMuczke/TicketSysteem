<x-layouts::app :title="__('Categorie aanmaken')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading>{{ __('Categorie aanmaken') }}</flux:heading>

        <form action="{{ route('categories.store') }}" method="POST"
              class="flex max-w-lg flex-col gap-4 rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            @csrf

            <flux:input name="naam" label="{{ __('Naam') }}" :value="old('naam')" required autofocus />

            <flux:select name="kleur" label="{{ __('Kleur') }}" required>
                @foreach ($colors as $value => $label)
                    <option value="{{ $value }}" @selected(old('kleur', 'sky') === $value)>{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:text class="text-sm text-neutral-500">
                {{ __('De technische sleutel wordt automatisch bepaald en blijft gelijk bij het wijzigen van de naam.') }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('categories.index') }}" variant="ghost">
                    {{ __('Annuleer') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Opslaan') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
