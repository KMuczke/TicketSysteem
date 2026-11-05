<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
        static::created(function (Ticket $ticket) {
            if (blank($ticket->ticket_number)) {
                $ticket->updateQuietly([
                    'ticket_number' => static::formatNumber($ticket->id),
                ]);
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
     * The user who reported the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
