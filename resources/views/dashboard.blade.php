<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <flux:heading>{{ __('Dashboard') }}</flux:heading>
            <flux:button href="{{ route('tickets.index') }}" variant="primary">
                {{ __('Alle tickets') }}
            </flux:button>
        </div>

        {{-- Kerncijfers --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-800">
                <span class="text-sm font-medium text-neutral-500">{{ __('Totaal tickets') }}</span>
                <div class="mt-3 text-3xl font-semibold">{{ $stats['totaal'] }}</div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-800">
                <span class="text-sm font-medium text-neutral-500">{{ __('Niet toegewezen') }}</span>
                <div class="mt-3 text-3xl font-semibold">{{ $stats['niet_toegewezen'] }}</div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-800">
                <span class="text-sm font-medium text-neutral-500">{{ __('Kritiek') }}</span>
                <div class="mt-3 text-3xl font-semibold text-red-500">{{ $stats['kritiek'] }}</div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-800">
                <span class="text-sm font-medium text-neutral-500">{{ __('Open tickets') }}</span>
                <div class="mt-3 text-3xl font-semibold">{{ $stats['open'] }}</div>
            </div>
        </div>

        {{-- Gemiddelde doorloop tijd --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-800">
            <span class="text-sm font-medium text-neutral-500">{{ __('Gemiddelde doorloop tijd') }}</span>
            <div class="mt-3 text-3xl font-semibold">
                {{ $gemiddeldeDoorloopijd ?? '—' }}
            </div>
            <p class="mt-1 text-sm text-neutral-500">
                {{ __('Gemiddelde tijd tussen aanmaken en sluiten van een ticket.') }}
            </p>
        </div>

        {{-- Grafieken --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Open tickets per status') }}</flux:heading>
                <div class="mt-4 h-72">
                    @if (array_sum($statusChart['data']) > 0)
                        <canvas
                            data-chart-type="doughnut"
                            data-chart='@json($statusChart)'
                        ></canvas>
                    @else
                        <div class="flex h-full items-center justify-center text-sm text-neutral-500">
                            {{ __('Geen open tickets') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Open tickets per developer') }}</flux:heading>
                <div class="mt-4 h-72">
                    @if (! empty($developerChart['data']))
                        <canvas
                            data-chart-type="bar"
                            data-chart='@json($developerChart)'
                        ></canvas>
                    @else
                        <div class="flex h-full items-center justify-center text-sm text-neutral-500">
                            {{ __('Geen open tickets') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
