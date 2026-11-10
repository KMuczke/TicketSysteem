<x-layouts::app :title="__('Categorieën')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <flux:heading>{{ __('Categorieën') }}</flux:heading>
            <flux:button href="{{ route('categories.create') }}" variant="primary">
                {{ __('Nieuwe categorie') }}
            </flux:button>
        </div>

        @if (session('success'))
            <flux:callout icon="check-circle" variant="success">
                {{ session('success') }}
            </flux:callout>
        @endif

        @if (session('error'))
            <flux:callout icon="exclamation-triangle" variant="danger">
                {{ session('error') }}
            </flux:callout>
        @endif

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="min-w-full text-sm">
                <thead class="bg-neutral-50 dark:bg-neutral-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Naam</th>
                        <th class="px-4 py-3 text-left font-medium">Kleur</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="px-4 py-3">{{ $category->naam }}</td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $category->kleur }}" size="sm">
                                    {{ $category->colorLabel() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $category->is_active ? 'green' : 'red' }}" size="sm">
                                    {{ $category->is_active ? __('Actief') : __('Gedeactiveerd') }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <flux:button href="{{ route('categories.edit', $category) }}" size="sm" variant="ghost">
                                        {{ __('Bewerk') }}
                                    </flux:button>

                                    <form method="POST" action="{{ route('categories.toggle-active', $category) }}">
                                        @csrf
                                        @method('PATCH')
                                        <flux:button type="submit" size="sm" variant="ghost">
                                            {{ $category->is_active ? __('Deactiveer') : __('Activeer') }}
                                        </flux:button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-neutral-500">
                                {{ __('Geen categorieën gevonden') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
