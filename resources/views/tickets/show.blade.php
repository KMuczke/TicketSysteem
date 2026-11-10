<x-layouts::app :title="__('Ticket') . ' ' . $ticket->ticket_number">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl">{{ __('Ticket') }} {{ $ticket->ticket_number }}</flux:heading>
                <flux:badge color="{{ $ticket->statusColor() }}">
                    {{ $ticket->statusLabel($ticket->status) }}
                </flux:badge>
                @if ($ticket->assignee_id === auth()->id())
                    <flux:badge color="blue">
                        {{ __('Aan jou toegewezen') }}
                    </flux:badge>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button href="{{ route('tickets.index') }}" variant="ghost" icon="arrow-left">
                    {{ __('Terug naar overzicht') }}
                </flux:button>
                @if ($ticket->assignee_id === null && $ticket->isAssignableBy(auth()->user()))
                    <form action="{{ route('tickets.assign', $ticket) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <flux:button type="submit" variant="primary" icon="user-plus">
                            {{ __('Oppakken') }}
                        </flux:button>
                    </form>
                @endif
                @if ($ticket->isReleasableBy(auth()->user()))
                    <form action="{{ route('tickets.release', $ticket) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <flux:button type="submit" variant="ghost" icon="arrow-uturn-left">
                            {{ __('Loslaten') }}
                        </flux:button>
                    </form>
                @endif
                @foreach ($ticket->availableStatusTransitionsFor(auth()->user()) as $nextStatus)
                    <form action="{{ route('tickets.status', $ticket) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                        <flux:button type="submit" variant="{{ $nextStatus->buttonVariant() }}" icon="arrow-right">
                            {{ $nextStatus->actionLabel($ticket->statusEnum()) }}
                        </flux:button>
                    </form>
                @endforeach
                @if ($ticket->isEditableBy(auth()->user()))
                    <flux:button href="{{ route('tickets.edit', $ticket) }}" variant="primary" icon="pencil-square">
                        {{ __('Bewerk') }}
                    </flux:button>
                @endif
                @if (auth()->user()->isAdmin())
                    <form action="{{ route('tickets.destroy', $ticket) }}" method="POST"
                          onsubmit="return confirm('{{ __('Ticket verwijderen?') }}')">
                        @csrf
                        @method('DELETE')
                        <flux:button type="submit" variant="danger" icon="trash">
                            {{ __('Verwijder') }}
                        </flux:button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('success'))
            <flux:callout icon="check-circle" variant="success">
                {{ session('success') }}
            </flux:callout>
        @endif

        @if (
            auth()->user()->isDeveloper()
            && $ticket->assignee_id === null
            && $ticket->status === 'nieuw'
            && auth()->user()->hasActiveAssignedTicket()
        )
            <flux:callout icon="information-circle" variant="warning">
                {{ __('Je hebt al een actief ticket opgepakt. Laat dat eerst los voordat je dit ticket oppakt.') }}
            </flux:callout>
        @endif

        @if ($errors->any())
            <flux:callout icon="exclamation-triangle" variant="danger">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <section class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
                    <flux:heading size="lg">{{ __('Gegevens') }}</flux:heading>

                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Nummer') }}</dt>
                            <dd class="font-mono">{{ $ticket->ticket_number }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Status') }}</dt>
                            <dd>
                                <flux:badge color="{{ $ticket->statusColor() }}" size="sm">
                                    {{ $ticket->statusLabel($ticket->status) }}
                                </flux:badge>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Categorie') }}</dt>
                            <dd>
                                <flux:badge color="{{ $ticket->category?->kleur ?? 'zinc' }}" size="sm">
                                    {{ $ticket->category?->naam ?? $ticket->categorie }}
                                </flux:badge>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Applicatie/website') }}</dt>
                            <dd>
                                @if ($ticket->application)
                                    @if ($ticket->application->url)
                                        <a href="{{ $ticket->application->url }}" target="_blank" rel="noopener noreferrer"
                                           class="text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $ticket->application->naam }}
                                        </a>
                                    @else
                                        {{ $ticket->application->naam }}
                                    @endif
                                @else
                                    &mdash;
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Prioriteit') }}</dt>
                            <dd>
                                <flux:badge color="{{ $ticket->priorityColor() }}" size="sm">
                                    {{ $ticket->priorityLabel($ticket->prioriteit) }}
                                </flux:badge>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Naam') }}</dt>
                            <dd>{{ $ticket->naam }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Melder') }}</dt>
                            <dd>
                                {{ $ticket->user?->name ?? __('Onbekend') }}
                                @if ($ticket->user?->email)
                                    <span class="block text-sm text-neutral-500">{{ $ticket->user->email }}</span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Toegewezen aan') }}</dt>
                            <dd>
                                @if ($ticket->assignee)
                                    {{ $ticket->assignee->name }}
                                    @if ($ticket->assignee_id === auth()->id())
                                        <span class="text-sm text-neutral-500">({{ __('jij') }})</span>
                                    @endif
                                @else
                                    {{ __('Niet toegewezen') }}
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Aangemaakt') }}</dt>
                            <dd>{{ $ticket->created_at?->format('d-m-Y H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Laatst bijgewerkt') }}</dt>
                            <dd>{{ $ticket->updated_at?->format('d-m-Y H:i') }}</dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-neutral-500">{{ __('Beschrijving') }}</dt>
                            <dd class="whitespace-pre-line">{{ $ticket->beschrijving ?: '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
                    <flux:heading size="lg">
                        {{ __('Reacties') }}
                        <span class="text-sm font-normal text-neutral-500">({{ $ticket->comments->count() }})</span>
                    </flux:heading>

                    <div class="mt-4 flex flex-col gap-3">
                        @forelse ($ticket->comments->sortByDesc('created_at') as $comment)
                            <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-sm">
                                        <span class="font-medium">{{ $comment->user?->name ?? __('Onbekend') }}</span>
                                        <span class="text-neutral-500">&middot; {{ $comment->created_at?->format('d-m-Y H:i') }}</span>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        @if ($comment->isOwnedBy(auth()->user()))
                                            <flux:button
                                                href="{{ route('tickets.comments.edit', [$ticket, $comment]) }}"
                                                size="xs"
                                                variant="ghost"
                                                icon="pencil-square"
                                            >
                                                {{ __('Bewerk') }}
                                            </flux:button>
                                        @endif

                                        @if ($comment->isDeletableBy(auth()->user()))
                                            <form action="{{ route('tickets.comments.destroy', [$ticket, $comment]) }}" method="POST"
                                                  onsubmit="return confirm('{{ __('Reactie verwijderen?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <flux:button type="submit" size="xs" variant="ghost" icon="trash">
                                                    {{ __('Verwijder') }}
                                                </flux:button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-2 whitespace-pre-line text-sm">{{ $comment->body }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-neutral-500">{{ __('Nog geen reacties.') }}</p>
                        @endforelse
                    </div>

                    <form action="{{ route('tickets.comments.store', $ticket) }}" method="POST" class="mt-4 flex flex-col gap-3">
                        @csrf

                        <flux:textarea name="body" label="{{ __('Nieuwe reactie') }}" rows="3"
                                       placeholder="{{ __('Schrijf een reactie...') }}" required />

                        <div>
                            <flux:button type="submit" size="sm" variant="primary">
                                {{ __('Reactie plaatsen') }}
                            </flux:button>
                        </div>
                    </form>
                </section>

                <section class="rounded-xl border border-amber-200 bg-amber-50/60 p-6 dark:border-amber-500/30 dark:bg-amber-500/5">
                    <div class="flex items-center gap-2">
                        <flux:icon name="lock-closed" class="size-5 text-amber-600 dark:text-amber-400" />
                        <flux:heading size="lg">
                            {{ __('Notities') }}
                            <span class="text-sm font-normal text-neutral-500">({{ $notes->count() }})</span>
                        </flux:heading>
                    </div>

                    <p class="mt-1 text-sm text-neutral-500">
                        {{ __('Privénotities: alleen jij kunt deze zien, ook beheerders niet.') }}
                    </p>

                    <div class="mt-4 flex flex-col gap-3">
                        @forelse ($notes as $note)
                            <div class="rounded-lg border border-amber-200 bg-white p-4 dark:border-amber-500/30 dark:bg-neutral-900">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-sm text-neutral-500">
                                        {{ $note->created_at?->format('d-m-Y H:i') }}
                                    </span>

                                    <form action="{{ route('tickets.notes.destroy', [$ticket, $note]) }}" method="POST"
                                          onsubmit="return confirm('{{ __('Notitie verwijderen?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" size="xs" variant="ghost" icon="trash">
                                            {{ __('Verwijder') }}
                                        </flux:button>
                                    </form>
                                </div>

                                <div class="mt-2 whitespace-pre-line text-sm">{{ $note->body }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-neutral-500">{{ __('Nog geen notities.') }}</p>
                        @endforelse
                    </div>

                    <form action="{{ route('tickets.notes.store', $ticket) }}" method="POST" class="mt-4 flex flex-col gap-3">
                        @csrf

                        <flux:textarea name="note" label="{{ __('Nieuwe notitie') }}" rows="3"
                                       placeholder="{{ __('Alleen zichtbaar voor jou...') }}" required />

                        <div>
                            <flux:button type="submit" size="sm" variant="primary">
                                {{ __('Notitie opslaan') }}
                            </flux:button>
                        </div>
                    </form>
                </section>
            </div>

            <aside class="flex flex-col gap-6">
                @if ($ticket->isClassificationEditableBy(auth()->user()))
                    <section class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
                        <flux:heading size="lg">{{ __('Classificatie') }}</flux:heading>

                        <form action="{{ route('tickets.classification', $ticket) }}" method="POST"
                              class="mt-4 flex flex-col gap-4">
                            @csrf
                            @method('PATCH')

                            <flux:select name="categorie" label="{{ __('Categorie') }}">
                                @foreach ($categories as $value => $label)
                                    <option value="{{ $value }}" @selected(old('categorie', $ticket->categorie) === $value)>{{ $label }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select name="prioriteit" label="{{ __('Prioriteit') }}">
                                @foreach ($priorities as $value => $label)
                                    <option value="{{ $value }}" @selected(old('prioriteit', $ticket->prioriteit) === $value)>{{ $label }}</option>
                                @endforeach
                            </flux:select>

                            <div>
                                <flux:button type="submit" size="sm" variant="primary">
                                    {{ __('Classificatie opslaan') }}
                                </flux:button>
                            </div>
                        </form>
                    </section>
                @endif

                <section class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
                    <flux:heading size="lg">{{ __('Geschiedenis') }}</flux:heading>

                    <ol class="mt-4 flex flex-col gap-4">
                        @forelse ($ticket->activities->sortByDesc('created_at') as $activity)
                            <li class="flex gap-3">
                                <div class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-500 dark:bg-neutral-800">
                                    <flux:icon name="{{ $activity->icon() }}" class="size-4" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-2">
                                        <span class="text-sm font-medium">
                                            {{ $activity->description ?: $activity->typeLabel() }}
                                        </span>
                                        <span class="text-xs text-neutral-500">
                                            {{ $activity->created_at?->format('d-m-Y H:i') }}
                                        </span>
                                    </div>

                                    <div class="text-xs text-neutral-500">
                                        {{ $activity->user?->name ?? __('Systeem') }}
                                    </div>

                                    @if ($activity->fieldLabel())
                                        <div class="mt-1 flex flex-wrap items-center gap-1 text-xs">
                                            <span class="text-neutral-400">{{ $activity->fieldLabel() }}:</span>
                                            <span class="text-neutral-400 line-through">
                                                {{ str()->limit($activity->oldValueLabel() ?? '—', 60) }}
                                            </span>
                                            <span class="text-neutral-400">&rarr;</span>
                                            <span class="font-medium">
                                                {{ str()->limit($activity->newValueLabel() ?? '—', 60) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-neutral-500">{{ __('Nog geen geschiedenis.') }}</li>
                        @endforelse
                    </ol>
                </section>
            </aside>
        </div>
    </div>
</x-layouts::app>
