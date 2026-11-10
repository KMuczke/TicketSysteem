<x-layouts::app :title="__('Applicatie bewerken')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading>{{ __('Applicatie bewerken') }}</flux:heading>

        <form action="{{ route('applications.update', $application) }}" method="POST"
              class="flex max-w-lg flex-col gap-4 rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            @csrf
            @method('PUT')

            <flux:input name="naam" label="{{ __('Naam') }}" :value="old('naam', $application->naam)" required autofocus />
            <flux:input name="url" type="url" label="{{ __('Website (optioneel)') }}"
                        placeholder="https://example.com" :value="old('url', $application->url)" />

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('applications.index') }}" variant="ghost">
                    {{ __('Annuleer') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Opslaan') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
