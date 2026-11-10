<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'type',
        'field',
        'old_value',
        'new_value',
        'description',
    ];

    /**
     * The ticket this activity belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * The user who triggered the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A human readable label for the activity type.
     */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'created' => __('Ticket aangemaakt'),
            'updated' => __('Ticket bijgewerkt'),
            'commented' => __('Reactie geplaatst'),
            'comment_updated' => __('Reactie bijgewerkt'),
            'comment_removed' => __('Reactie verwijderd'),
            'assigned' => __('Ticket opgepakt'),
            'released' => __('Ticket losgelaten'),
            default => __('Wijziging'),
        };
    }

    /**
     * The icon that fits the activity type.
     */
    public function icon(): string
    {
        return match ($this->type) {
            'created' => 'plus-circle',
            'updated' => 'pencil-square',
            'commented' => 'chat-bubble-left',
            'comment_updated' => 'pencil-square',
            'comment_removed' => 'trash',
            'assigned' => 'user-plus',
            'released' => 'arrow-uturn-left',
            default => 'clock',
        };
    }

    /**
     * A human readable label for the changed field.
     */
    public function fieldLabel(): ?string
    {
        if (blank($this->field)) {
            return null;
        }

        return match ($this->field) {
            'naam' => __('Naam'),
            'beschrijving' => __('Beschrijving'),
            'status' => __('Status'),
            'categorie' => __('Categorie'),
            'prioriteit' => __('Prioriteit'),
            default => ucfirst($this->field),
        };
    }

    /**
     * The old value, translated for known fields.
     */
    public function oldValueLabel(): ?string
    {
        return $this->valueLabel($this->old_value);
    }

    /**
     * The new value, translated for known fields.
     */
    public function newValueLabel(): ?string
    {
        return $this->valueLabel($this->new_value);
    }

    /**
     * Translate a stored value based on the changed field.
     */
    private function valueLabel(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($this->field) {
            'status' => Ticket::statusLabel($value),
            'categorie' => Ticket::categoryLabel($value),
            'prioriteit' => Ticket::priorityLabel($value),
            default => $value,
        };
    }
}
