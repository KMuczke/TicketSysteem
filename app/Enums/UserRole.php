<?php

namespace App\Enums;

enum UserRole: string
{
    case Melder = 'melder';
    case Developer = 'developer';
    case Beheerder = 'beheerder';

    /**
     * Get the human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Melder => __('Melder'),
            self::Developer => __('Developer'),
            self::Beheerder => __('Beheerder'),
        };
    }

    /**
     * Get the description of what the role does.
     */
    public function description(): string
    {
        return match ($this) {
            self::Melder => __('Medewerker of klant die een probleem meldt en de voortgang volgt.'),
            self::Developer => __('Pakt tickets op, communiceert met de melder en lost het probleem op.'),
            self::Beheerder => __('Verdeelt werk, beheert gebruikers en instellingen en bewaakt de voortgang.'),
        };
    }

    /**
     * Get the badge color that fits the role.
     */
    public function color(): string
    {
        return match ($this) {
            self::Melder => 'zinc',
            self::Developer => 'blue',
            self::Beheerder => 'purple',
        };
    }

    /**
     * Get the available roles as value => label pairs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
