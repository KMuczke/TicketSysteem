<x-layouts::app :title="__('Gebruiker bewerken')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading>{{ __('Gebruiker bewerken') }}</flux:heading>

        <form action="{{ route('users.update', $user) }}" method="POST"
              class="flex max-w-lg flex-col gap-4 rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            @csrf
            @method('PUT')

            <flux:input name="name" label="{{ __('Naam') }}" :value="old('name', $user->name)" required autofocus />
            <flux:input name="email" type="email" label="{{ __('E-mail') }}" :value="old('email', $user->email)" required />
            <flux:input name="password" type="password" label="{{ __('Nieuw wachtwoord (optioneel)') }}"
                        :placeholder="__('Laat leeg om het huidige wachtwoord te behouden')" viewable />

            <flux:select name="role" label="{{ __('Rol') }}" required>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $user->role->value) === $value)>{{ $label }}</option>
                @endforeach
            </flux:select>

            <div class="flex flex-col gap-1 text-sm text-neutral-500">
                @foreach (\App\Enums\UserRole::cases() as $role)
                    <p><span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $role->label() }}</span> &ndash; {{ $role->description() }}</p>
                @endforeach
            </div>

            <flux:checkbox name="is_active" value="1" :label="__('Actief')"
                           :checked="old('is_active', $user->isActive())"
                           :disabled="$user->is(auth()->user())" />

            @if ($user->is(auth()->user()))
                <flux:text class="text-sm text-neutral-500">
                    {{ __('Je kunt je eigen account niet deactiveren of je beheerdersrol afnemen.') }}
                </flux:text>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('users.index') }}" variant="ghost">
                    {{ __('Annuleer') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Opslaan') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
