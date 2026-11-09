<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Nieuw = 'nieuw';
    case Toegewezen = 'toegewezen';
    case InBehandeling = 'in_behandeling';
    case WachtOpMelder = 'wacht_op_melder';
    case Opgelost = 'opgelost';
    case Afgewezen = 'afgewezen';
    case Gesloten = 'gesloten';

    /**
     * Get the human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Nieuw => __('Nieuw'),
            self::Toegewezen => __('Toegewezen'),
            self::InBehandeling => __('In behandeling'),
            self::WachtOpMelder => __('Wacht op melder'),
            self::Opgelost => __('Opgelost'),
            self::Afgewezen => __('Afgewezen'),
            self::Gesloten => __('Gesloten'),
        };
    }

    /**
     * Get the badge color that fits the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Nieuw => 'red',
            self::Toegewezen => 'purple',
            self::InBehandeling => 'amber',
            self::WachtOpMelder => 'sky',
            self::Opgelost => 'emerald',
            self::Afgewezen => 'rose',
            self::Gesloten => 'green',
        };
    }

    /**
     * Get the statuses this status may transition to.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Nieuw => [self::Toegewezen, self::Afgewezen],
            self::Toegewezen => [self::InBehandeling],
            self::InBehandeling => [self::WachtOpMelder, self::Opgelost],
            self::WachtOpMelder => [self::InBehandeling],
            self::Opgelost => [self::Gesloten, self::InBehandeling],
            self::Afgewezen => [self::Gesloten],
            self::Gesloten => [],
        };
    }

    /**
     * Determine whether a transition to the given status is allowed.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Determine whether this status is a final status.
     */
    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Get the label for the button that moves a ticket to this status.
     */
    public function actionLabel(?self $from = null): string
    {
        return match ($this) {
            self::InBehandeling => $from === self::Opgelost
                ? __('Heropenen')
                : ($from === self::WachtOpMelder ? __('Weer in behandeling') : __('In behandeling nemen')),
            self::WachtOpMelder => __('Wacht op melder'),
            self::Opgelost => __('Opgelost'),
            self::Afgewezen => __('Afwijzen'),
            self::Gesloten => __('Sluiten'),
            default => $this->label(),
        };
    }

    /**
     * Get the button variant that fits the status change.
     */
    public function buttonVariant(): string
    {
        return match ($this) {
            self::InBehandeling, self::Opgelost => 'primary',
            self::Afgewezen => 'danger',
            default => 'ghost',
        };
    }

    /**
     * Get the available statuses as value => label pairs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
