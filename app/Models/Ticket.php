<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assignee_id',
        'ticket_number',
        'naam',
        'beschrijving',
        'status',
    ];

    /**
     * Bootstrap the model and its events.
     */
    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (blank($ticket->status)) {
                $ticket->status = TicketStatus::Nieuw->value;
            }
        });

        static::created(function (Ticket $ticket) {
            if (blank($ticket->ticket_number)) {
                $ticket->updateQuietly([
                    'ticket_number' => static::formatNumber($ticket->id),
                ]);
            }

            $ticket->recordActivity(
                type: 'created',
                description: __('Ticket aangemaakt'),
            );
        });

        static::updated(function (Ticket $ticket) {
            $fields = [
                'naam' => __('Naam'),
                'beschrijving' => __('Beschrijving'),
                'status' => __('Status'),
            ];

            foreach ($fields as $field => $label) {
                if (! $ticket->wasChanged($field)) {
                    continue;
                }

                $ticket->recordActivity(
                    type: 'updated',
                    field: $field,
                    oldValue: $ticket->getOriginal($field),
                    newValue: $ticket->getAttribute($field),
                    description: $label.' '.__('gewijzigd'),
                );
            }
        });
    }

    /**
     * Build the human readable number for a ticket id.
     */
    public static function formatNumber(int $id): string
    {
        return 'TKT-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * A human readable label for a status.
     */
    public static function statusLabel(?string $status): string
    {
        return TicketStatus::tryFrom((string) $status)?->label() ?? (string) $status;
    }

    /**
     * The badge color that fits the status.
     */
    public function statusColor(): string
    {
        return $this->statusEnum()?->color() ?? 'zinc';
    }

    /**
     * The status as an enum value.
     */
    public function statusEnum(): ?TicketStatus
    {
        return TicketStatus::tryFrom((string) $this->status);
    }

    /**
     * Determine whether this ticket may transition to the given status.
     */
    public function canTransitionTo(TicketStatus|string $status): bool
    {
        $current = $this->statusEnum();
        $target = $status instanceof TicketStatus ? $status : TicketStatus::tryFrom($status);

        return $current !== null && $target !== null && $current->canTransitionTo($target);
    }

    /**
     * Store an entry in the ticket history.
     */
    public function recordActivity(
        string $type,
        ?string $field = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $description = null,
    ): TicketActivity {
        return $this->activities()->create([
            'user_id' => auth()->id(),
            'type' => $type,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
        ]);
    }

    /**
     * Determine whether the given user may edit this ticket.
     */
    public function isEditableBy(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }

    /**
     * Determine whether the given developer may pick up this ticket.
     */
    public function isAssignableBy(User $user): bool
    {
        if (! $user->isDeveloper()) {
            return false;
        }

        if (in_array($this->status, [
            TicketStatus::Opgelost->value,
            TicketStatus::Afgewezen->value,
            TicketStatus::Gesloten->value,
        ], true)) {
            return false;
        }

        if ($this->assignee_id !== null) {
            return $this->assignee_id === $user->id
                && $this->status === TicketStatus::Toegewezen->value;
        }

        return $this->status === TicketStatus::Nieuw->value
            && ! $user->hasActiveAssignedTicket();
    }

    /**
     * Determine whether the given developer may release this ticket.
     */
    public function isReleasableBy(User $user): bool
    {
        if (! $user->isDeveloper() || $this->assignee_id !== $user->id) {
            return false;
        }

        return ! in_array($this->status, [
            TicketStatus::Opgelost->value,
            TicketStatus::Afgewezen->value,
            TicketStatus::Gesloten->value,
        ], true);
    }

    /**
     * Determine whether the given user may change the status of this ticket.
     */
    public function isStatusEditableBy(User $user): bool
    {
        return $user->isAdmin()
            || ($user->isDeveloper() && $this->assignee_id === $user->id);
    }

    /**
     * Get the status transitions the given user may perform right now.
     *
     * @return array<int, TicketStatus>
     */
    public function availableStatusTransitionsFor(User $user): array
    {
        $current = $this->statusEnum();

        if ($current === null || ! $this->isStatusEditableBy($user)) {
            return [];
        }

        return array_values(array_filter(
            $current->allowedTransitions(),
            fn (TicketStatus $status) => $status !== TicketStatus::Toegewezen
        ));
    }

    /**
     * The user who reported the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The developer who picked up the ticket.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * The comments that belong to the ticket.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * The private notes that belong to the ticket.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(TicketNote::class);
    }

    /**
     * The history of the ticket.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }
}
