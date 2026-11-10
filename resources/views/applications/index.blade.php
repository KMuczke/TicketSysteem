<x-layouts::app :title="__('Applicaties')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <flux:heading>{{ __('Applicaties') }}</flux:heading>
            <flux:button href="{{ route('applications.create') }}" variant="primary">
                {{ __('Nieuwe applicatie') }}
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
                        <th class="px-4 py-3 text-left font-medium">Website</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="px-4 py-3">{{ $application->naam }}</td>
                            <td class="px-4 py-3">
                                @if ($application->url)
                                    <a href="{{ $application->url }}" target="_blank" rel="noopener noreferrer"
                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $application->url }}
                                    </a>
                                @else
                                    <span class="text-neutral-500">{{ __('Geen website opgegeven') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $application->is_active ? 'green' : 'red' }}" size="sm">
                                    {{ $application->is_active ? __('Actief') : __('Gedeactiveerd') }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <flux:button href="{{ route('applications.edit', $application) }}" size="sm" variant="ghost">
                                        {{ __('Bewerk') }}
                                    </flux:button>

                                    <form method="POST" action="{{ route('applications.toggle-active', $application) }}">
                                        @csrf
                                        @method('PATCH')
                                        <flux:button type="submit" size="sm" variant="ghost">
                                            {{ $application->is_active ? __('Deactiveer') : __('Activeer') }}
                                        </flux:button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-neutral-500">
                                {{ __('Geen applicaties gevonden') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
