<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Kritiek = 'kritiek';
    case Hoog = 'hoog';
    case Normaal = 'normaal';
    case Laag = 'laag';

    /**
     * Get the human readable label for the priority.
     */
    public function label(): string
    {
        return match ($this) {
            self::Kritiek => __('Kritiek'),
            self::Hoog => __('Hoog'),
            self::Normaal => __('Normaal'),
            self::Laag => __('Laag'),
        };
    }

    /**
     * Get the badge color that fits the priority.
     */
    public function color(): string
    {
        return match ($this) {
            self::Kritiek => 'red',
            self::Hoog => 'orange',
            self::Normaal => 'sky',
            self::Laag => 'zinc',
        };
    }

    /**
     * Get the available priorities as value => label pairs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $priority) => [$priority->value => $priority->label()])
            ->all();
    }
}
