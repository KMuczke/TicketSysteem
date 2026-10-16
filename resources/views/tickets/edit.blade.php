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

            <flux:select name="status" label="{{ __('Status') }}">
                <option value="open" @selected(old('status', $ticket->status) === 'open')>Open</option>
                <option value="in_behandeling" @selected(old('status', $ticket->status) === 'in_behandeling')>In behandeling</option>
                <option value="gesloten" @selected(old('status', $ticket->status) === 'gesloten')>Gesloten</option>
            </flux:select>

            <flux:select name="prioriteit" label="{{ __('Prioriteit') }}">
                <option value="laag" @selected(old('prioriteit', $ticket->prioriteit) === 'laag')>Laag</option>
                <option value="gemiddeld" @selected(old('prioriteit', $ticket->prioriteit) === 'gemiddeld')>Gemiddeld</option>
                <option value="hoog" @selected(old('prioriteit', $ticket->prioriteit) === 'hoog')>Hoog</option>
                <option value="kritiek" @selected(old('prioriteit', $ticket->prioriteit) === 'kritiek')>Kritiek</option>
            </flux:select>

            <flux:select name="developer_id" label="{{ __('Developer') }}">
                <option value="" @selected(blank(old('developer_id', $ticket->developer_id)))>Niet toegewezen</option>
                @foreach ($developers as $developer)
                    <option value="{{ $developer->id }}" @selected((string) old('developer_id', $ticket->developer_id) === (string) $developer->id)>
                        {{ $developer->name }}
                    </option>
                @endforeach
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
