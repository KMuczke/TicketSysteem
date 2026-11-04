<x-layouts::app :title="__('Gebruikers')">
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <flux:heading>{{ __('Gebruikers') }}</flux:heading>
            @if ($isAdmin)
                <flux:button href="{{ route('users.create') }}" variant="primary">
                    {{ __('Nieuwe gebruiker') }}
                </flux:button>
            @endif
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
                        <th class="px-4 py-3 text-left font-medium">E-mail</th>
                        <th class="px-4 py-3 text-left font-medium">Rol</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        @if ($isAdmin)
                            <th class="px-4 py-3 text-left font-medium">Acties</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="px-4 py-3">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $user->isAdmin() ? 'purple' : 'zinc' }}" size="sm">
                                    {{ $user->role->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $user->isActive() ? 'green' : 'red' }}" size="sm">
                                    {{ $user->isActive() ? __('Actief') : __('Gedeactiveerd') }}
                                </flux:badge>
                            </td>
                            @if ($isAdmin)
                                <td class="px-4 py-3">
                                    <div class="flex gap-3">
                                        <flux:button href="{{ route('users.edit', $user) }}" size="sm" variant="ghost">
                                            {{ __('Bewerk') }}
                                        </flux:button>

                                        @unless ($user->is(auth()->user()))
                                            <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <flux:button type="submit" size="sm" variant="ghost">
                                                    {{ $user->isActive() ? __('Deactiveer') : __('Activeer') }}
                                                </flux:button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 5 : 4 }}" class="px-4 py-8 text-center text-neutral-500">
                                {{ __('Geen gebruikers gevonden') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
</x-layouts::app>
